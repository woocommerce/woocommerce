# Compare two POT files: msgids (with context and plural) and translator comments.
import re, sys
def parse(path):
    entries = {}
    for block in open(path, encoding='utf8').read().split('\n\n'):
        lines = block.split('\n')
        def field(name):
            out, on = [], False
            for l in lines:
                if l.startswith(name + ' '):
                    on = True; out.append(l[len(name) + 1:])
                elif on and l.startswith('"'):
                    out.append(l)
                else:
                    on = False
            return ''.join(s[1:-1] for s in out) if out else None
        msgid = field('msgid')
        if not msgid:
            continue
        key = (field('msgctxt'), msgid, field('msgid_plural'))
        entries[key] = sorted(l[3:] for l in lines if l.startswith('#. '))
    return entries
a, b = parse(sys.argv[1]), parse(sys.argv[2])
only_a = [k for k in a if k not in b]; only_b = [k for k in b if k not in a]
ca = sum(1 for k in a if a[k]); cb = sum(1 for k in b if b[k])
lost = [k for k in a if k in b and a[k] and not b[k]]
gained = [k for k in a if k in b and b[k] and not a[k]]
changed = [k for k in a if k in b and a[k] and b[k] and a[k] != b[k]]
print(f'entries {len(a)} vs {len(b)}; only first {len(only_a)}, only second {len(only_b)}; with comments {ca} vs {cb}; comment lost {len(lost)}, gained {len(gained)}, changed {len(changed)}')
for name, items in (('only first', only_a), ('only second', only_b), ('lost', lost), ('changed', changed)):
    for k in items[:int(sys.argv[3]) if len(sys.argv) > 3 else 5]:
        print(' ', name, repr(k)[:150], '|', a.get(k), '|', b.get(k))
