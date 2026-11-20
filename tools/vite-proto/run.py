#!/usr/bin/env python3
"""Run a command, record wall time and peak summed RSS of its process tree. Appends one JSON line."""
import json, os, subprocess, sys, time, threading

def tree_rss(root):
    kids = {}
    rss = {}
    for pid in os.listdir('/proc'):
        if not pid.isdigit():
            continue
        try:
            with open(f'/proc/{pid}/stat') as f:
                s = f.read()
            ppid = int(s.rsplit(')', 1)[1].split()[1])
            with open(f'/proc/{pid}/statm') as f:
                rss[int(pid)] = int(f.read().split()[1]) * 4096
            kids.setdefault(ppid, []).append(int(pid))
        except Exception:
            pass
    total, stack = 0, [root]
    while stack:
        p = stack.pop()
        total += rss.get(p, 0)
        stack.extend(kids.get(p, []))
    return total

label, out, cwd, cmd = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4]
log = open(f'/tmp/run-{label}.log', 'w')
start = time.time()
proc = subprocess.Popen(cmd, shell=True, cwd=cwd, stdout=log, stderr=subprocess.STDOUT, start_new_session=True)
peak = 0
def poll():
    global peak
    while proc.poll() is None:
        peak = max(peak, tree_rss(proc.pid))
        time.sleep(0.1)
t = threading.Thread(target=poll); t.start()
code = proc.wait(); t.join()
rec = {'label': label, 'wall_s': round(time.time() - start, 2), 'peak_rss_mb': round(peak / 1e6), 'exit': code, 'load': os.getloadavg()[0]}
with open(out, 'a') as f:
    f.write(json.dumps(rec) + '\n')
print(json.dumps(rec))
