#!/usr/bin/env python3
"""Run the isolated, fixed-order TypeScript compiler experiment."""
import argparse
import os
from pathlib import Path
import platform
import subprocess
import sys

HERE = Path(__file__).resolve().parent


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--data-dir', required=True, type=Path, help='New or empty results directory outside the source checkout')
    parser.add_argument('--node', required=True, type=Path, help='Node 24.15.0 executable')
    parser.add_argument('--source', type=Path, default=HERE.parents[2], help='WooCommerce checkout (default: this repository)')
    parser.add_argument('--split-email', action='store_true', help='Also run three paired trials preserving TS5 email declarations')
    args = parser.parse_args()
    if sys.version_info < (3, 12) or platform.system() != 'Darwin' or platform.machine() != 'arm64':
        parser.error('Requires Python 3.12+ on macOS arm64 with APFS')
    source, data, node = args.source.resolve(), args.data_dir.resolve(), args.node.resolve()
    if data.is_relative_to(source):
        parser.error('--data-dir must be outside the source checkout')
    if data.exists() and any(data.iterdir()):
        parser.error('--data-dir must be empty')
    if subprocess.check_output([str(node), '--version'], text=True).strip() != 'v24.15.0':
        parser.error('--node must be Node 24.15.0')
    if not os.environ.get('TMPDIR'):
        parser.error('TMPDIR must point to an APFS volume with enough space')
    if Path(os.environ['TMPDIR']).resolve().is_relative_to(source):
        parser.error('TMPDIR must be outside the source checkout')
    data.mkdir(parents=True, exist_ok=True)
    env = os.environ.copy()
    env.update(TS7_DATA_DIR=str(data), TS7_SOURCE=str(source), TS7_NODE=str(node))
    for script in ('prepare.py', 'provision.py'):
        subprocess.run([sys.executable, str(HERE / script)], cwd=source, env=env, check=True)
    import json
    location = json.loads((data / 'location.json').read_text())
    snapshot, tools = Path(location['source']), Path(location['tools'])
    compilers = [([str(node), str(snapshot / 'node_modules/typescript/lib/tsc.js')], '5.7.3')]
    compilers += [([str(tools / version / 'node_modules/@typescript/typescript-darwin-arm64/lib/tsc')], version) for version in ('7.0.2', '7.1.0-dev.20261101.1')]
    for command, version in compilers:
        actual = subprocess.check_output(command + ['--version'], cwd=snapshot, text=True).strip()
        if actual != 'Version ' + version:
            raise RuntimeError(f'Expected TypeScript {version}, got {actual}')
    command = [str(node), str(snapshot / 'packages/js/internal-build/scripts/generate-types.mjs'), 'generate']
    result = subprocess.run(command, cwd=snapshot, env=env, text=True, capture_output=True, check=True)
    (data / 'generate-types.txt').write_text(result.stdout + result.stderr)
    if result.stdout.count('Generated types for ') != 5 or result.stdout.count('Applied patch: ') != 5:
        raise RuntimeError('Expected five generated packages and five applied patches; see generate-types.txt')
    subprocess.run([sys.executable, str(HERE / 'run_trials.py')], cwd=snapshot, env=env, check=True)
    for script in ('analyze.py', 'control.py', 'consumer_checks.py'):
        subprocess.run([sys.executable, str(HERE / script)], cwd=snapshot, env=env, check=True)
    if args.split_email:
        subprocess.run([sys.executable, str(HERE / 'run_split_trials.py')], cwd=snapshot, env=env, check=True)
    print(f'Results retained in {data}; snapshot location is recorded in location.json')


if __name__ == '__main__':
    main()
