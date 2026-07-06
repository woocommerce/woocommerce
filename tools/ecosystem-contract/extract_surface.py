#!/usr/bin/env python3
"""Extract the public API surface of a WooCommerce release tree.

Surfaces extracted (static, no PHP runtime):
  classes     — FQN → {kind: class|interface|trait, abstract, final,
                       methods: {name → {visibility, static, abstract, params, return}}}
  functions   — global function name → signature (wc_*/woocommerce_* only)
  hooks       — do_action/apply_filters literal names → max arg count seen
  selectors   — wc-block-*/woocommerce-* class-name string literals (markup surface, v1 proxy)

Output: JSON. Feed two of these to diff_surface.py.
"""
import argparse
import json
import os
import re
import sys

RE_NS = re.compile(r"^\s*namespace\s+([A-Za-z0-9_\\]+)\s*;", re.M)
RE_DECL = re.compile(
    r"^[ \t]*((?:abstract\s+|final\s+|readonly\s+)*)(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)",
    re.M)
RE_METHOD = re.compile(
    r"((?:public\s+|protected\s+|private\s+|static\s+|abstract\s+|final\s+)*)function\s+(&?\s*[A-Za-z_][A-Za-z0-9_]*)\s*\(",
    re.S)
RE_FUNC_GLOBAL = re.compile(r"^function\s+((?:wc|woocommerce)_[a-z0-9_]+)\s*\(", re.M | re.I)
RE_HOOK_FIRE = re.compile(r"\b(do_action|apply_filters)(?:_ref_array|_deprecated)?\s*\(\s*(['\"])((?:(?!\2).)+)\2")
RE_SELECTOR = re.compile(r"(?:wc-block-[A-Za-z0-9_-]+|wp-block-woocommerce-[A-Za-z0-9_-]+)")

STRIP_COMMENTS = re.compile(r"/\*.*?\*/|//[^\n]*|#[^\n!]*", re.S)


def find_matching_brace(text, start):
    """Index just past the brace that closes the '{' at text[start]."""
    depth = 0
    i = start
    n = len(text)
    while i < n:
        c = text[i]
        if c == "{":
            depth += 1
        elif c == "}":
            depth -= 1
            if depth == 0:
                return i + 1
        elif c in "'\"":
            q = c
            i += 1
            while i < n and text[i] != q:
                i += 2 if text[i] == "\\" else 1
        i += 1
    return n


def parse_params(text, start):
    """Parse the parameter list starting at the '(' at text[start]. Returns (params_str, end)."""
    depth = 0
    i = start
    n = len(text)
    while i < n:
        c = text[i]
        if c == "(":
            depth += 1
        elif c == ")":
            depth -= 1
            if depth == 0:
                return text[start + 1:i], i + 1
        elif c in "'\"":
            q = c
            i += 1
            while i < n and text[i] != q:
                i += 2 if text[i] == "\\" else 1
        i += 1
    return "", n


def normalize_params(raw):
    """Split params on top-level commas; keep type, name, has-default flag."""
    out = []
    depth = 0
    buf = ""
    for c in raw + ",":
        if c in "([{":
            depth += 1
        elif c in ")]}":
            depth -= 1
        if c == "," and depth == 0:
            p = " ".join(buf.split())
            if p:
                has_default = "=" in p
                sig = p.split("=", 1)[0].strip()
                out.append({"sig": sig, "optional": has_default})
            buf = ""
        else:
            buf += c
    return out


def parse_return(text, pos):
    m = re.match(r"\s*:\s*([?A-Za-z0-9_\\| ]+)", text[pos:pos + 120])
    return " ".join(m.group(1).split()) if m else None


RE_FILTER_ASSIGN = re.compile(r"\$(\w+)\s*=\s*apply_filters\s*\(\s*['\"]([^'\"]+)['\"]")
RE_CALLABLE_ASSIGN = re.compile(r"\$(\w+)\s*=\s*(?:call_user_func(?:_array)?\s*\(\s*)?\$(\w+)\s*\(")
RE_RETURN_FILTER = re.compile(r"return\s+apply_filters\s*\(\s*['\"]([^'\"]+)['\"]")
RE_RETURN_VAR = re.compile(r"return\s+\$(\w+)\s*;")
RE_RETURN_THIS_CALL = re.compile(r"return\s+\$this->(\w+)\s*\(")


def filter_taint(mbody):
    """Hooks whose filter return values can flow into this method's return value,
    plus same-class methods whose return value it forwards ($this->x(...)).

    Catches the WC_Email::send_notification(): bool fatal class: a declared
    return type over a value third parties influence via apply_filters — either
    the filtered value itself or the result of calling a filter-provided callable.
    """
    tainted = {m.group(1): m.group(2) for m in RE_FILTER_ASSIGN.finditer(mbody)}
    for _ in range(3):  # propagate $w = $v(...) where $v holds a filtered callable
        grew = False
        for m in RE_CALLABLE_ASSIGN.finditer(mbody):
            w, v = m.group(1), m.group(2)
            if v in tainted and w not in tainted:
                tainted[w] = tainted[v]
                grew = True
        if not grew:
            break
    hooks = {m.group(1) for m in RE_RETURN_FILTER.finditer(mbody)}
    hooks |= {tainted[m.group(1)] for m in RE_RETURN_VAR.finditer(mbody) if m.group(1) in tainted}
    forwards = {m.group(1) for m in RE_RETURN_THIS_CALL.finditer(mbody)}
    return hooks, forwards


def scan_file(path, rel):
    with open(path, encoding="utf-8", errors="replace") as f:
        raw = f.read()
    text = STRIP_COMMENTS.sub("", raw)
    ns = RE_NS.search(text)
    prefix = (ns.group(1) + "\\") if ns else ""

    classes = {}
    for dm in RE_DECL.finditer(text):
        mods, kind, name = dm.group(1), dm.group(2), dm.group(3)
        if kind == "enum":
            continue
        brace = text.find("{", dm.end())
        if brace == -1:
            continue
        end = find_matching_brace(text, brace)
        body = text[brace + 1:end - 1]
        methods = {}
        forwards_map = {}
        for mm in RE_METHOD.finditer(body):
            meth_mods = mm.group(1) or ""
            mname = mm.group(2).lstrip("& \t")
            vis = ("private" if "private" in meth_mods
                   else "protected" if "protected" in meth_mods else "public")
            params, pend = parse_params(body, mm.end() - 1)
            mbrace = body.find("{", pend)
            msemi = body.find(";", pend)
            hooks, forwards = set(), set()
            if mbrace != -1 and (msemi == -1 or mbrace < msemi):  # has a body
                mend = find_matching_brace(body, mbrace)
                hooks, forwards = filter_taint(body[mbrace + 1:mend - 1])
            methods[mname] = {
                "visibility": vis,
                "static": "static" in meth_mods,
                "abstract": "abstract" in meth_mods or kind == "interface",
                "params": normalize_params(params),
                "return": parse_return(body, pend),
            }
            if hooks:
                methods[mname]["tainted_return"] = sorted(hooks)
            if forwards:
                forwards_map[mname] = forwards
        # one intra-class hop: a method returning $this->x(...) inherits x's taint
        # (two passes cover forward-then-forward chains like a → b → filtered)
        for _ in range(2):
            for mname, fwd in forwards_map.items():
                inherited = set(methods[mname].get("tainted_return", []))
                for callee in fwd:
                    inherited.update(methods.get(callee, {}).get("tainted_return", []))
                if inherited:
                    methods[mname]["tainted_return"] = sorted(inherited)
        classes[prefix + name] = {
            "kind": kind,
            "abstract": "abstract" in mods,
            "final": "final" in mods,
            "file": rel,
            "methods": methods,
        }

    functions = {}
    if not prefix:  # only global-namespace wc_* functions are public API
        for fm in RE_FUNC_GLOBAL.finditer(text):
            params, pend = parse_params(text, text.find("(", fm.start()))
            functions[fm.group(1).lower()] = {
                "params": normalize_params(params),
                "return": parse_return(text, pend),
                "file": rel,
            }

    hooks = {}
    for hm in RE_HOOK_FIRE.finditer(text):
        name = hm.group(3)
        if "$" in name or "{" in name:
            name = re.sub(r"\{[^}]*\}|\$[A-Za-z0-9_>-]+", "*", name)
        _, pend = parse_params(text, text.find("(", hm.start()))
        argseg = text[hm.start():pend]
        depth = argc = 0
        for c in argseg:
            if c in "([{":
                depth += 1
            elif c in ")]}":
                depth -= 1
            elif c == "," and depth == 1:
                argc += 1
        hooks[name] = max(hooks.get(name, 0), argc)  # argc = args after hook name

    selectors = set(RE_SELECTOR.findall(raw))
    return classes, functions, hooks, selectors


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("tree", help="path to extracted WooCommerce release (dir containing woocommerce/)")
    ap.add_argument("--out", required=True)
    args = ap.parse_args()

    root = args.tree
    if os.path.isdir(os.path.join(root, "woocommerce")):
        root = os.path.join(root, "woocommerce")

    all_classes, all_functions, all_hooks, all_selectors = {}, {}, {}, set()
    nfiles = 0
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [d for d in dirnames if d not in ("node_modules", ".git", "vendor")]
        for fn in filenames:
            if not fn.endswith(".php"):
                continue
            path = os.path.join(dirpath, fn)
            rel = os.path.relpath(path, root)
            if rel.startswith(("packages/",)):
                pass  # packaged deps (action-scheduler, blocks) ship in the release: still surface
            classes, functions, hooks, selectors = scan_file(path, rel)
            nfiles += 1
            all_classes.update(classes)
            all_functions.update(functions)
            for h, argc in hooks.items():
                all_hooks[h] = max(all_hooks.get(h, 0), argc)
            all_selectors |= selectors

    version = ""
    main_file = os.path.join(root, "woocommerce.php")
    if os.path.exists(main_file):
        with open(main_file, encoding="utf-8", errors="replace") as f:
            vm = re.search(r"Version:\s*([0-9.]+)", f.read())
            version = vm.group(1) if vm else ""

    out = {
        "version": version,
        "files": nfiles,
        "classes": all_classes,
        "functions": all_functions,
        "hooks": all_hooks,
        "selectors": sorted(all_selectors),
    }
    os.makedirs(os.path.dirname(os.path.abspath(args.out)), exist_ok=True)
    with open(args.out, "w") as f:
        json.dump(out, f)
    print(f"{version or root}: {nfiles} files, {len(all_classes)} classes/interfaces, "
          f"{len(all_functions)} wc_* functions, {len(all_hooks)} hooks, {len(all_selectors)} block selectors")


if __name__ == "__main__":
    main()
