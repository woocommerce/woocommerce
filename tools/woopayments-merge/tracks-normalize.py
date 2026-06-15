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
DATE_RE = re.compile(r'^\d{4}-\d{2}-\d{2}$')
DEC_RE = re.compile(r'^\d+\.\d{1,4}$')  # monetary/decimal amount (e.g. order_total 75.00)
VER_RE = re.compile(r'^\d+\.\d+(\.\d+)+([.-][0-9A-Za-z.]+)?$')  # 3+ part dotted version
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
        if TS_RE.match(v) or DATE_RE.match(v):
            return 'str:<ts>'
        if VER_RE.match(v):
            return 'str:<ver>'
        if DEC_RE.match(v):
            return 'str:<num>'
        if re.fullmatch(r'\d+', v):
            return 'str:<n>'
        return 'str:%s' % v  # stable enum string — kept (drift is caught)
    if v is None:
        return 'null'
    if isinstance(v, (list, dict)):
        return 'json:<%s>' % type(v).__name__
    return 'other'


def main():
    # The wpcom-local sink is shared by every store in the checkout, so filter to one store's
    # events via --store <woocommerce_store_id> (the per-install UUID on every client+server event).
    store = None
    if '--store' in sys.argv:
        store = sys.argv[sys.argv.index('--store') + 1]
    # Synthetic sources injected by the sink tooling itself — not real store telemetry, so excluded.
    synthetic = {'mock', 'helper_smoke', 'codex', 'smoke'}
    lines = set()
    for raw in sys.stdin:
        raw = raw.strip()
        if not raw or raw[0] != '{':
            continue
        try:
            rec = json.loads(raw)
        except Exception:
            continue
        if str(rec.get('source', '')) in synthetic:
            continue
        # Accept both the wpcom-local sink shape ({event_name, properties}) and the
        # legacy capture shape ({event, props}).
        name = re.sub(r'^wcadmin_', '', str(rec.get('event_name') or rec.get('event') or ''))
        props = rec.get('properties') or rec.get('props') or {}
        if store is not None and str(props.get('store_id', '')) != store:
            continue
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
