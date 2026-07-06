#!/usr/bin/env python3
"""Diff two extracted API surfaces (extract_surface.py output) and emit breaking changes.

Additions are not breaking — with one critical exception: adding a method to an
interface (or an abstract method to a class) breaks every existing implementer.
That is the 10.9.0 FeedInterface class of failure.

Output: JSON list of {change, kind, surface, member, detail, severity}.
"""
import argparse
import json


def sig_tuple(m):
    required = [p["sig"] for p in m["params"] if not p["optional"]]
    return (len(required), m.get("return"), m.get("static"))


def param_types(m):
    out = []
    for p in m["params"]:
        parts = p["sig"].rsplit("$", 1)
        out.append(parts[0].strip() if len(parts) == 2 else "")
    return out


def diff(old, new):
    changes = []

    def add(change, kind, surface, member=None, detail="", severity="high"):
        changes.append({"change": change, "kind": kind, "surface": surface,
                        "member": member, "detail": detail, "severity": severity})

    oc, nc = old["classes"], new["classes"]
    for fqn, o in oc.items():
        n = nc.get(fqn)
        kind = o["kind"]
        if n is None:
            add("removed", kind, fqn, detail=f"{kind} removed (was {o['file']})")
            continue
        if not o["final"] and n["final"]:
            add("made_final", kind, fqn, detail="subclasses now fatal", severity="high")
        for mname, om in o["methods"].items():
            nm = n["methods"].get(mname)
            public = om["visibility"] == "public"
            sev = "high" if public else "medium"
            if nm is None:
                if om["visibility"] != "private":
                    add("method_removed", kind, fqn, mname, severity=sev)
                continue
            if om["visibility"] != "private":
                if nm["visibility"] == "private" or (public and nm["visibility"] != "public"):
                    add("method_visibility_reduced", kind, fqn, mname,
                        detail=f"{om['visibility']} → {nm['visibility']}", severity=sev)
                o_req, o_ret, o_static = sig_tuple(om)
                n_req, n_ret, n_static = sig_tuple(nm)
                if n_req > o_req:
                    add("required_param_added", kind, fqn, mname,
                        detail=f"required params {o_req} → {n_req}", severity=sev)
                if o_ret != n_ret and o_ret is not None:
                    add("return_type_changed", kind, fqn, mname,
                        detail=f"{o_ret} → {n_ret}", severity="medium")
                if o_static != n_static:
                    add("static_changed", kind, fqn, mname, severity=sev)
                opt, npt = param_types(om), param_types(nm)
                for i, (a, b) in enumerate(zip(opt, npt)):
                    if a and b and a != b:
                        add("param_type_changed", kind, fqn, mname,
                            detail=f"param {i + 1}: {a} → {b}", severity="medium")
        # Newly-declared return type over a filter-influenced value: third-party
        # filter callbacks returning a non-conforming type now fatal (the
        # WC_Email::send_notification(): bool / 10.9.3 class of failure).
        for mname, nm in n["methods"].items():
            om = o["methods"].get(mname)
            if not nm.get("return") or not nm.get("tainted_return"):
                continue
            if om is not None and om.get("return"):
                continue  # type was already declared in the old release
            for hook in nm["tainted_return"]:
                add("strict_return_over_filtered_value", "hook", hook,
                    f"{fqn}::{mname}",
                    detail=f"{fqn}::{mname}() {'is new and' if om is None else 'newly'} "
                           f"declares `: {nm['return']}` over a value influenced by the "
                           f"`{hook}` filter — non-conforming filter return values fatal",
                    severity="high")

        # THE critical case: new abstract members break existing implementers/subclassers
        for mname, nm in n["methods"].items():
            if mname in o["methods"]:
                continue
            if kind == "interface":
                add("interface_method_added", kind, fqn, mname,
                    detail="existing implementers no longer satisfy the contract → fatal on load",
                    severity="critical")
            elif nm.get("abstract"):
                add("abstract_method_added", kind, fqn, mname,
                    detail="existing subclasses no longer satisfy the contract → fatal on load",
                    severity="critical")

    for name, o in old["functions"].items():
        n = new["functions"].get(name)
        if n is None:
            add("function_removed", "function", name)
            continue
        o_req = len([p for p in o["params"] if not p["optional"]])
        n_req = len([p for p in n["params"] if not p["optional"]])
        if n_req > o_req:
            add("required_param_added", "function", name,
                detail=f"required params {o_req} → {n_req}")

    for hook, argc in old["hooks"].items():
        n_argc = new["hooks"].get(hook)
        if n_argc is None:
            add("hook_removed", "hook", hook, severity="high")
        elif n_argc < argc:
            add("hook_args_reduced", "hook", hook,
                detail=f"args {argc} → {n_argc}: callbacks requesting more args fatal",
                severity="medium")

    old_sel, new_sel = set(old["selectors"]), set(new["selectors"])
    for sel in sorted(old_sel - new_sel):
        add("selector_removed", "selector", sel, severity="low")

    return changes


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("old")
    ap.add_argument("new")
    ap.add_argument("--out", required=True)
    args = ap.parse_args()
    with open(args.old) as f:
        old = json.load(f)
    with open(args.new) as f:
        new = json.load(f)
    changes = diff(old, new)
    result = {"old_version": old.get("version"), "new_version": new.get("version"),
              "changes": changes}
    with open(args.out, "w") as f:
        json.dump(result, f, indent=1)
    by_sev = {}
    for c in changes:
        by_sev[c["severity"]] = by_sev.get(c["severity"], 0) + 1
    print(f"{old.get('version')} → {new.get('version')}: {len(changes)} candidate breaking changes {by_sev}")


if __name__ == "__main__":
    main()
