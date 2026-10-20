#!/usr/bin/env python3
"""Register this historical pair in a new checkout using local Git objects."""
import argparse
import hashlib
import json
import os
import shutil
from pathlib import Path
import subprocess
import tempfile

PACKAGE = Path(__file__).resolve().parent
SUITE = Path('.ai/review/evals') / PACKAGE.parents[1].name
ENV = dict(os.environ, GIT_NO_LAZY_FETCH='1', GIT_TERMINAL_PROMPT='0')


def git(repo, *args, env=None):
    return subprocess.check_output(['git', '-C', str(repo), *args], env=env or ENV)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--policy-repo', required=True, type=Path)
    parser.add_argument('--source-repo', required=True, type=Path)
    parser.add_argument('--output', required=True, type=Path)
    args = parser.parse_args()
    pins = json.loads((PACKAGE / 'sources.json').read_text())
    template = json.loads((PACKAGE / 'cases.template.json').read_text())
    for commit in (pins['base'], pins['head']):
        git(args.source_repo, 'cat-file', '-e', commit + '^{commit}')
    objects = git(args.source_repo, 'rev-list', '--objects', '--missing=print',
                  pins['base'], pins['head'], '--')
    if any(line.startswith(b'?') for line in objects.splitlines()):
        raise ValueError('Historical source objects are incomplete')
    original = git(args.source_repo, 'diff', '--binary', '--full-index',
                   '--no-ext-diff', '--no-textconv', '--no-color',
                   pins['base'], pins['head'], '--')
    if original != (PACKAGE / 'original.patch').read_bytes():
        raise ValueError('Original patch differs from the pinned source change')
    with tempfile.TemporaryDirectory(prefix='historical-pair-') as temp:
        env = dict(ENV, GIT_INDEX_FILE=str(Path(temp) / 'index'))
        for name, expected in pins['patch_sha256'].items():
            patch = PACKAGE / name
            if hashlib.sha256(patch.read_bytes()).hexdigest() != expected:
                raise ValueError('Patch hash differs: ' + name)
            git(args.source_repo, 'read-tree', pins['base'], env=env)
            git(args.source_repo, 'apply', '--cached', '--check', str(patch), env=env)
    output = args.output.resolve()
    if output.exists():
        raise ValueError('Output must not exist; preparation never overwrites files')
    for repo in (args.policy_repo, args.source_repo):
        root = Path(git(repo, 'rev-parse', '--show-toplevel').decode().strip()).resolve()
        if output == root or root in output.parents:
            raise ValueError('Output must be outside the input repositories')
    policy = git(args.policy_repo, 'rev-parse', 'HEAD').decode().strip()
    git(args.policy_repo, 'clone', '--shared', '--no-checkout', '--',
        str(args.policy_repo.resolve()), str(output))
    git(output, 'sparse-checkout', 'set', '--no-cone', '/.ai/', '/AGENTS.md',
        '/CLAUDE.md', '/ai-review-config.json')
    git(output, 'checkout', '--detach', policy)
    objects = git(args.source_repo, 'rev-parse', '--path-format=absolute',
                  '--git-path', 'objects').decode().strip()
    with (output / '.git/objects/info/alternates').open('a') as stream:
        stream.write(objects + '\n')
    target = output / SUITE
    for name in pins['patch_sha256']:
        destination = target / 'groups' / PACKAGE.name / name
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(PACKAGE / name, destination)
    cases = json.loads((target / 'cases.json').read_text())
    ids = {case['id'] for case in cases['cases']}
    for case in template['cases']:
        if case['id'] in ids:
            raise ValueError('Case ID is already registered: ' + case['id'])
        cases['cases'].append(case)
    groups = cases.setdefault('groups', [])
    if any(group['id'] == template['groups'][0]['id'] for group in groups):
        raise ValueError('Group is already registered')
    groups.extend(template['groups'])
    (target / 'cases.json').write_text(json.dumps(cases, indent=2) + '\n')
    print(json.dumps({'output': str(output), 'policy_commit': policy,
                      'source_base': pins['base'],
                      'group': template['groups'][0]['id']}, indent=2))


if __name__ == '__main__':
    main()
