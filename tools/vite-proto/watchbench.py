#!/usr/bin/env python3
"""Time a watcher: start to first finished build, then edit files and time each rebuild.
Usage: watchbench.py <label> <out.jsonl> <cwd> <cmd> <done-regex> <error-regex> <repeats> <file> [file ...]
A build is finished at the last line matching done-regex before the output goes quiet."""
import json, os, re, signal, subprocess, sys, threading, time

label, out, cwd, cmd, done_re, err_re, repeats = sys.argv[1:8]
files = sys.argv[8:]
QUIET = float(os.environ.get('QUIET', '4'))
done, err = re.compile(done_re), re.compile(err_re)
marks, errors, lines = [], [], 0
lock = threading.Lock()
log = open(f'/tmp/watch-{label}.log', 'w')
proc = subprocess.Popen(cmd, shell=True, cwd=cwd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, start_new_session=True, text=True, errors='replace')

def reader():
    global lines
    for line in proc.stdout:
        now = time.time()
        log.write(f'{now:.3f} {line}'); log.flush()
        with lock:
            lines += 1
            if done.search(line): marks.append(now)
            if err.search(line): errors.append(line.strip()[:200])
threading.Thread(target=reader, daemon=True).start()

def tree_rss(root):
    kids, rss = {}, {}
    for pid in os.listdir('/proc'):
        if not pid.isdigit(): continue
        try:
            s = open(f'/proc/{pid}/stat').read()
            ppid = int(s.rsplit(')', 1)[1].split()[1])
            rss[int(pid)] = int(open(f'/proc/{pid}/statm').read().split()[1]) * 4096
            kids.setdefault(ppid, []).append(int(pid))
        except Exception: pass
    total, stack = 0, [root]
    while stack:
        p = stack.pop(); total += rss.get(p, 0); stack.extend(kids.get(p, []))
    return total
peak = 0
def poll():
    global peak
    while proc.poll() is None:
        peak = max(peak, tree_rss(proc.pid)); time.sleep(0.25)
threading.Thread(target=poll, daemon=True).start()

EXPECT = int(os.environ.get('EXPECT_START', '1'))
def wait_build(since, timeout=600, need=1):
    """Seconds from `since` to the last done line once output is quiet."""
    deadline = time.time() + timeout
    while time.time() < deadline:
        time.sleep(0.1)
        with lock:
            after = [m for m in marks if m > since]
        if len(after) >= need and time.time() - after[-1] > QUIET:
            return round(after[-1] - since, 2), len(after)
    return None, 0

originals = {f: open(f).read() for f in files}
rec = {'label': label}
try:
    t0 = time.time()
    rec['start_s'], rec['start_builds'] = wait_build(t0, need=EXPECT)
    rec['rebuilds'] = {}
    for f in files:
        times = []
        for n in range(int(repeats)):
            time.sleep(1)
            extra = f'\n.wc-watch-{n} {{ color: red; }}\n' if f.endswith('css') else f'\nglobalThis.__wcWatch = {n};\n'
            with open(f, 'a') as h: h.write(extra)
            te = time.time()
            times.append(wait_build(te, 45))
        rec['rebuilds'][os.path.relpath(f, '/home/user/workspace/repo')] = times
finally:
    for f, text in originals.items():
        open(f, 'w').write(text)
    time.sleep(0.5)
    try: os.killpg(proc.pid, signal.SIGKILL)
    except Exception: pass
rec['peak_rss_mb'] = round(peak / 1e6)
rec['errors'] = errors[:5]
with open(out, 'a') as h: h.write(json.dumps(rec) + '\n')
print(json.dumps(rec))
