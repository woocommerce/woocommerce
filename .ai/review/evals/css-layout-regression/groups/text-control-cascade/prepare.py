#!/usr/bin/env python3
"""Register this historical pair in a new checkout using local Git objects."""
import argparse
import base64
import hashlib
import io
import json
import os
import shutil
from pathlib import Path
import subprocess
import tempfile
import tarfile
from urllib.request import urlopen

PACKAGE = Path(__file__).resolve().parent
SUITE = Path('.ai/review/evals') / PACKAGE.parents[1].name
ENV = dict(os.environ, GIT_NO_LAZY_FETCH='1', GIT_TERMINAL_PROMPT='0')


def git(repo, *args, env=None, input=None):
    return subprocess.check_output(['git', '-C', str(repo), *args], env=env or ENV, input=input)


def dependency_source(repo, pins):
    dependency = pins['upstream_dependency']
    with urlopen(dependency['url'], timeout=60) as response:
        archive = response.read()
    integrity = 'sha512-' + base64.b64encode(hashlib.sha512(archive).digest()).decode()
    if integrity != dependency['integrity']:
        raise ValueError('Upstream dependency archive integrity differs')
    with tarfile.open(fileobj=io.BytesIO(archive), mode='r:gz') as package:
        member = package.getmember(dependency['stylesheet'])
        if not member.isfile():
            raise ValueError('Upstream stylesheet must be a regular file')
        stylesheet = package.extractfile(member).read()
    reference = 'eval-context/wordpress-components-' + dependency['version']
    note = ('# Historical TextControl dependency source\n\n'
            + dependency['name'] + ' ' + dependency['version'] + '\n'
            + dependency['url'] + '\n' + integrity + '\n'
            + 'Archive member: ' + dependency['stylesheet'] + '\n'
            + 'style.css is the unmodified pinned upstream stylesheet.\n'
            + 'It accompanies the historical source; it is not part of either test patch.\n')
    with tempfile.TemporaryDirectory(prefix='historical-reference-') as temp:
        env = dict(ENV, GIT_INDEX_FILE=str(Path(temp) / 'index'),
                   GIT_AUTHOR_NAME='Eval fixture', GIT_AUTHOR_EMAIL='eval@example.invalid',
                   GIT_COMMITTER_NAME='Eval fixture', GIT_COMMITTER_EMAIL='eval@example.invalid',
                   GIT_AUTHOR_DATE='2000-01-01T00:00:00Z', GIT_COMMITTER_DATE='2000-01-01T00:00:00Z')
        git(repo, 'read-tree', pins['base'], env=env)
        for name, content in [('style.css', stylesheet), ('README.md', note.encode())]:
            path = reference + '/' + name
            if git(repo, 'ls-tree', pins['base'], '--', path):
                raise ValueError('Historical reference source path already exists')
            blob = git(repo, 'hash-object', '-w', '--stdin', '--no-filters', input=content).decode().strip()
            git(repo, 'update-index', '--add', '--cacheinfo', '100644', blob, path, env=env)
        tree = git(repo, 'write-tree', env=env).decode().strip()
        return git(repo, 'commit-tree', tree, '-p', pins['base'],
                   '-m', 'Attach pinned TextControl stylesheet for the historical eval', env=env).decode().strip()


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
    source_base = dependency_source(output, pins)
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
        case['base_commit'] = source_base
        cases['cases'].append(case)
    groups = cases.setdefault('groups', [])
    if any(group['id'] == template['groups'][0]['id'] for group in groups):
        raise ValueError('Group is already registered')
    groups.extend(template['groups'])
    (target / 'cases.json').write_text(json.dumps(cases, indent=2) + '\n')
    print(json.dumps({'output': str(output), 'policy_commit': policy,
                      'source_base': source_base, 'historical_base': pins['base'],
                      'group': template['groups'][0]['id']}, indent=2))


if __name__ == '__main__':
    main()
