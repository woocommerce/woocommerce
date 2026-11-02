#!/usr/bin/env python3
"""Measure PR Metrics history retrieval and exact checkout/restore equivalence."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import subprocess
import time

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--head', required=True)
parser.add_argument('--base', required=True)
parser.add_argument('--arm', choices=['full', 'blobless', 'shallow'], required=True)
parser.add_argument('--work', type=Path, required=True)
parser.add_argument('--output', type=Path, required=True)
args = parser.parse_args()
args.work = args.work.resolve()
args.output = args.output.resolve()
args.work.mkdir(parents=True, exist_ok=False)
args.output.mkdir(parents=True, exist_ok=False)
timings = {}

def git(*command, phase=None):
    started = time.perf_counter()
    result = subprocess.run(['git', *command], cwd=args.work, capture_output=True, text=True)
    elapsed = time.perf_counter() - started
    if phase:
        timings[phase] = elapsed
    with (args.output / 'git.log').open('a') as log:
        log.write(json.dumps({'command': list(command), 'seconds': elapsed, 'exit': result.returncode}) + '\n')
        log.write(result.stdout + result.stderr)
    result.check_returncode()
    return result.stdout.strip()

result = {'arm': args.arm, 'head': args.head, 'base': args.base, 'timings': timings}
try:
    git('init')
    git('config', 'gc.auto', '0')
    git('config', 'gc.autoPackLimit', '0')
    git('config', 'maintenance.auto', 'false')
    git('remote', 'add', 'origin', 'https://github.com/woocommerce/woocommerce.git')
    git('-c', 'protocol.version=2', 'fetch', '--no-tags', '--depth=1', 'origin', args.head, phase='initial_head_fetch')
    git('-c', 'core.hooksPath=/dev/null', 'checkout', '--detach', args.head, phase='initial_head_checkout')
    git('fetch', '--depth=1', '--no-tags', 'origin', args.base + ':refs/remotes/origin/trunk', phase='initial_base_fetch')
    assert git('rev-parse', 'origin/trunk') == args.base
    assert git('rev-parse', '--is-shallow-repository') == 'true'
    result['head_tree'] = git('rev-parse', args.head + '^{tree}')
    result['base_tree'] = git('rev-parse', args.base + '^{tree}')
    result['packed_bytes_before'] = sum(p.stat().st_size for p in (args.work / '.git/objects/pack').glob('*.pack'))
    if args.arm == 'full':
        git('fetch', '--no-tags', '--quiet', '--unshallow', 'origin', args.base + ':refs/remotes/origin/trunk', phase='history_fetch')
    elif args.arm == 'blobless':
        started = time.perf_counter()
        git('config', 'remote.origin.promisor', 'true')
        git('config', 'remote.origin.partialclonefilter', 'blob:none')
        git('fetch', '--no-tags', '--quiet', '--unshallow', '--filter=blob:none', 'origin', args.base + ':refs/remotes/origin/trunk')
        timings['history_fetch'] = time.perf_counter() - started
    else:
        timings['history_fetch'] = 0
    git('-c', 'core.hooksPath=/dev/null', 'checkout', '--quiet', args.base, phase='base_checkout')
    assert git('rev-parse', 'HEAD') == args.base
    assert not git('status', '--porcelain', '--untracked-files=no')
    result['base_lockfile_sha256'] = hashlib.sha256((args.work / 'pnpm-lock.yaml').read_bytes()).hexdigest()
    paths = ['plugins/woocommerce/tests', 'tools/compare-perf', '.github']
    git('restore', '--source', args.head, *paths, phase='restore_head_test_resources')
    # Verify the exact tree produced by applying head resources onto the baseline.
    git('add', '--', *paths)
    result['mixed_tree'] = git('write-tree')
    result['restored_resource_hashes'] = {}
    tracked = subprocess.check_output(['git', 'ls-files', '-z', '--', *paths], cwd=args.work).decode().split('\0')
    for name in filter(None, tracked):
        path = args.work / name
        content = os.readlink(path).encode() if path.is_symlink() else path.read_bytes()
        result['restored_resource_hashes'][name] = hashlib.sha256(content).hexdigest()
    assert result['restored_resource_hashes']
    result['shallow_after'] = git('rev-parse', '--is-shallow-repository')
    result['packed_bytes_after'] = sum(p.stat().st_size for p in (args.work / '.git/objects/pack').glob('*.pack'))
    result['packed_growth'] = result['packed_bytes_after'] - result['packed_bytes_before']
    result['pipeline_seconds'] = sum(timings[k] for k in ['history_fetch', 'base_checkout', 'restore_head_test_resources'])
    result['success'] = True
finally:
    (args.output / 'result.json').write_text(json.dumps(result, indent=2))
print(json.dumps({k: v for k, v in result.items() if k != 'restored_resource_hashes'}, indent=2))
