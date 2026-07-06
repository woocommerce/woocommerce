#!/usr/bin/env python3
"""Release gate: intersect a surface diff with the ecosystem usage index.

For every candidate breaking change, list the extensions/themes that touch that
surface and their active install counts, then apply the decision rules:

  tier 0 (unused)        — no corpus hit → allowed, changelog note
  tier 1 (low usage)     — hits but < --notify-threshold total installs → allowed with author notification
  tier 2 (high usage)    — ≥ threshold installs, or ANY critical-severity hit → BLOCKS the release tag
                           unless acknowledged in the ack file

Ack file (JSON list): [{"surface": "...", "member": "...", "reason": "...", "approved_by": "..."}]

Exit code: 0 clean / 2 blocked.
"""
import argparse
import json
import sqlite3
import sys

KIND_TO_USAGE = {
    # diff kind      → usage-index kinds that consume that surface
    "interface": ["class_implement", "class_extend", "class_ref"],
    "class": ["class_extend", "class_implement", "class_ref"],
    "trait": ["class_ref"],
    "function": ["function"],
    "hook": ["hook"],
    "selector": ["selector"],
}

# usage kinds that mean "will fatal", vs "may misbehave"
FATAL_USAGE = {"class_implement", "class_extend"}


def surface_consumers(db, kind, surface):
    usage_kinds = KIND_TO_USAGE.get(kind, [])
    if not usage_kinds:
        return []
    qmarks = ",".join("?" * len(usage_kinds))
    rows = db.execute(
        f"""SELECT kind, plugin, plugin_type, active_installs, refs
            FROM usage WHERE surface = ? AND kind IN ({qmarks})
            ORDER BY active_installs DESC""",
        [surface] + usage_kinds).fetchall()
    # selector index stores tokens like ".wc-block-foo" / "wc-block-foo": try both
    if not rows and kind == "selector":
        rows = db.execute(
            """SELECT kind, plugin, plugin_type, active_installs, refs
               FROM usage WHERE kind='selector' AND (surface = ? OR surface = ?)
               ORDER BY active_installs DESC""",
            ["." + surface, surface]).fetchall()
    return [{"usage": r[0], "plugin": r[1], "type": r[2],
             "active_installs": r[3], "refs": r[4]} for r in rows]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--diff", required=True)
    ap.add_argument("--index", required=True)
    ap.add_argument("--ack", help="JSON file of acknowledged breaks")
    ap.add_argument("--notify-threshold", type=int, default=100_000,
                    help="total active installs above which a break blocks the tag")
    ap.add_argument("--report", required=True, help="markdown report output path")
    ap.add_argument("--json-out", help="machine-readable findings output")
    args = ap.parse_args()

    with open(args.diff) as f:
        diff = json.load(f)
    acks = []
    if args.ack:
        with open(args.ack) as f:
            acks = json.load(f)
    ack_keys = {(a["surface"], a.get("member")) for a in acks}
    db = sqlite3.connect(args.index)

    findings = []
    for c in diff["changes"]:
        consumers = surface_consumers(db, c["kind"], c["surface"])
        total_installs = sum(x["active_installs"] for x in consumers)
        fatal_consumers = [x for x in consumers if x["usage"] in FATAL_USAGE]
        if not consumers:
            tier = 0
        elif c["severity"] == "critical" and fatal_consumers:
            tier = 2
        elif total_installs >= args.notify_threshold:
            tier = 2
        else:
            tier = 1
        acked = (c["surface"], c.get("member")) in ack_keys
        findings.append({**c, "consumers": consumers, "total_installs": total_installs,
                         "tier": tier, "acknowledged": acked})

    blocking = [f for f in findings if f["tier"] == 2 and not f["acknowledged"]]
    tier1 = [f for f in findings if f["tier"] == 1]
    tier0 = [f for f in findings if f["tier"] == 0]

    lines = [
        f"# Release gate report: {diff['old_version']} → {diff['new_version']}",
        "",
        f"**{len(findings)} candidate breaking changes** — "
        f"{len(blocking)} blocking, "
        f"{len([f for f in findings if f['tier'] == 2 and f['acknowledged']])} acknowledged, "
        f"{len(tier1)} notify-tier, {len(tier0)} unused-tier.",
        "",
    ]
    if blocking:
        lines.append("## 🛑 BLOCKING — high-impact breaks, unacknowledged\n")
        for f in blocking:
            member = f"::{f['member']}" if f["member"] else ""
            lines.append(f"### `{f['surface']}{member}` — {f['change']} ({f['severity']})")
            if f["detail"]:
                lines.append(f"\n{f['detail']}\n")
            lines.append(f"\n**Affected ({f['total_installs']:,} total installs):**\n")
            for x in f["consumers"][:15]:
                fatal = " ⚡fatal-class usage" if x["usage"] in FATAL_USAGE else ""
                lines.append(f"- {x['plugin']} ({x['active_installs']:,} installs) — {x['usage']}{fatal}")
            if len(f["consumers"]) > 15:
                lines.append(f"- …and {len(f['consumers']) - 15} more")
            lines.append("")
    if tier1:
        lines.append("## ⚠️ Notify authors (low install-base usage)\n")
        for f in tier1:
            member = f"::{f['member']}" if f["member"] else ""
            plugins = ", ".join(x["plugin"] for x in f["consumers"][:5])
            lines.append(f"- `{f['surface']}{member}` — {f['change']} → {plugins} "
                         f"({f['total_installs']:,} installs)")
        lines.append("")
    lines.append(f"## ✅ Unused surface ({len(tier0)}) — break freely, changelog note\n")
    for f in tier0[:30]:
        member = f"::{f['member']}" if f["member"] else ""
        lines.append(f"- `{f['surface']}{member}` — {f['change']}")
    if len(tier0) > 30:
        lines.append(f"- …and {len(tier0) - 30} more")

    with open(args.report, "w") as f:
        f.write("\n".join(lines) + "\n")
    if args.json_out:
        with open(args.json_out, "w") as f:
            json.dump(findings, f, indent=1)

    print(f"gate: {len(blocking)} blocking / {len(tier1)} notify / {len(tier0)} unused "
          f"→ report: {args.report}")
    if blocking:
        for f in blocking[:10]:
            member = f"::{f['member']}" if f["member"] else ""
            top = f["consumers"][0] if f["consumers"] else None
            top_s = f" (top: {top['plugin']}, {top['active_installs']:,} installs)" if top else ""
            print(f"  🛑 {f['change']}: {f['surface']}{member}{top_s}")
        sys.exit(2)


if __name__ == "__main__":
    main()
