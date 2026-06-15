#!/usr/bin/env python3
"""Normalize a Tracks capture (JSONL on stdin) into a churn-stable contract signature on stdout.

Encodes the telemetry-continuity boundary judgment (bc-manifest §0.3/§3.6): freeze what
consumers depend on (event name + prop keys + types + stable enum string values), ignore what
would wedge us (the auto-injected envelope; volatile values like ids/timestamps/numbers).
"""
import json
import re
import sys

ID_RE = re.compile(r'^(ch|pi|py|re|in|cus|pm|seti|po|dp|sub|prod|price|txn|wcpay)_[A-Za-z0-9]+$')
TS_RE = re.compile(r'^\d{4}-\d{2}-\d{2}[T ]')
# Volatile values that legitimately differ across installs/releases and so are NOT part of the
# contract — mask the value, keep the prop's presence/key/type. (Confirmed against real sink data:
# store_id is a UUID, wc_version is a dotted version; both differ between the reference and target.)
UUID_RE = re.compile(r'^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$', re.I)
VER_RE = re.compile(r'^\d+\.\d+(\.\d+)*([.-][0-9A-Za-z.]+)?$')
# Auto-injected Tracks envelope (client/Jetpack/identity) — not part of WCPay's contract.
ENVELOPE = {
    'blog_id', 'blog_tz', 'user_lang', 'device_type', 'anonid', 'url', 'referrer',
    '_via', '_ts', '_rt', '_dl', '_dr', '_lg', '_en', '_ui', '_ut', '_tz', '_aua',
}


def mask(v):
    if isinstance(v, bool):
        return 'bool:%s' % str(v).lower()
    if isinstance(v, (int, float)):
        return 'num:<n>'
    if isinstance(v, str):
        if ID_RE.match(v):
            return 'str:<id>'
        if UUID_RE.match(v):
            return 'str:<uuid>'
        if TS_RE.match(v):
            return 'str:<ts>'
        if VER_RE.match(v):
            return 'str:<ver>'
        if re.fullmatch(r'\d+', v):
            return 'str:<n>'
        return 'str:%s' % v  # stable enum string — kept (drift is caught)
    if v is None:
        return 'null'
    if isinstance(v, (list, dict)):
        return 'json:<%s>' % type(v).__name__
    return 'other'


def main():
    lines = set()
    for raw in sys.stdin:
        raw = raw.strip()
        if not raw or raw[0] != '{':
            continue
        try:
            rec = json.loads(raw)
        except Exception:
            continue
        name = re.sub(r'^wcadmin_', '', str(rec.get('event', '')))
        props = rec.get('props', {}) or {}
        keys = []
        for k in sorted(props.keys()):
            if k.startswith('_') or k in ENVELOPE:
                continue
            keys.append('%s=%s' % (k, mask(props[k])))
        lines.add('%s | %s' % (name, ' '.join(keys)))
    for line in sorted(lines):
        print(line)


if __name__ == '__main__':
    main()
