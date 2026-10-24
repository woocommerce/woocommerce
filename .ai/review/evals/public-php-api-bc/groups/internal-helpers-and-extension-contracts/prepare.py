#!/usr/bin/env python3
"""Prepare two native patch cases using local Git objects; never run a model."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import subprocess
import tempfile

PACKAGE = Path(__file__).resolve().parent
SUITE = Path('.ai/review/evals/public-php-api-bc')
GROUP = Path('groups/internal-helpers-and-extension-contracts')
PATCHES = ('regression/mcp-helper-removal.patch', 'controls/stripe-required-method.patch')
NOTE = ('\nDependency reference sources are under `eval-context/dependencies/`. '
        'Versions, upstream commits, and file hashes are listed in '
        '`eval-context/source-provenance.json`. These are source snapshots, '
        'not installed dependencies.\n')
ENV = dict(os.environ, GIT_NO_LAZY_FETCH='1', GIT_TERMINAL_PROMPT='0',
           GIT_CONFIG_GLOBAL=os.devnull, GIT_CONFIG_NOSYSTEM='1')
for key in ('GIT_DIR', 'GIT_WORK_TREE', 'GIT_INDEX_FILE', 'GIT_OBJECT_DIRECTORY',
            'GIT_ALTERNATE_OBJECT_DIRECTORIES', 'GIT_CONFIG', 'GIT_CONFIG_COUNT',
            'GIT_CONFIG_PARAMETERS'):
    ENV.pop(key, None)


def git(repo, *args, data=None, env=None):
    return subprocess.check_output(
        ['git', '-c', 'core.hooksPath=/dev/null', '-c', 'gc.auto=0',
         '-c', 'maintenance.auto=false', '-C', str(repo), *args],
        input=data, env=env or ENV)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--policy-repo', required=True, type=Path)
    parser.add_argument('--source-repo', required=True, type=Path)
    parser.add_argument('--adapter-repo', required=True, type=Path)
    parser.add_argument('--stripe-file', required=True, type=Path)
    parser.add_argument('--output', required=True, type=Path)
    parser.add_argument('--agents-policy', type=Path, default=PACKAGE / 'agents-policy.patch',
                        help='AGENTS overlay; use an empty file for a baseline without it')
    args = parser.parse_args()
    pins = json.loads((PACKAGE / 'sources.json').read_text())
    agents_policy = args.agents_policy.read_bytes()
    agents_policy_sha256 = hashlib.sha256(agents_policy).hexdigest()
    policy = git(args.policy_repo, 'rev-parse', 'HEAD').decode().strip()
    # Source objects must already exist locally. No implicit network fetches.
    for repo, commit in [(args.source_repo, pins['woocommerce']['base']),
                         (args.source_repo, pins['woocommerce']['head'])] + [
                             (args.adapter_repo, d['commit'])
                             for d in pins['dependency_snapshots']]:
        git(repo, 'cat-file', '-e', commit + '^{commit}')
    consumer = pins['external_consumer']
    stripe = args.stripe_file.read_bytes()
    if hashlib.sha256(stripe).hexdigest() != consumer['sha256']:
        raise ValueError('Stripe source does not match the pinned public file')
    original = git(args.source_repo, 'diff', '--full-index', '--no-color',
                   '--no-ext-diff', '--no-textconv', '--no-relative',
                   '--src-prefix=a/', '--dst-prefix=b/', '--diff-algorithm=myers',
                   '--indent-heuristic', '--find-renames', '--unified=3',
                   '--inter-hunk-context=0', '-O' + os.devnull,
                   pins['woocommerce']['base'], pins['woocommerce']['head'])
    if original != (PACKAGE / PATCHES[0]).read_bytes():
        raise ValueError('MCP patch differs from the pinned original change')
    if args.output.exists():
        raise ValueError('Output must not exist; preparation never overwrites a checkout')
    output = args.output.resolve()
    for repo in (args.policy_repo, args.source_repo, args.adapter_repo):
        root = Path(git(repo, 'rev-parse', '--show-toplevel').decode().strip()).resolve()
        if output == root or root in output.parents:
            raise ValueError('Output must be outside every input repository')
    output.parent.mkdir(parents=True, exist_ok=True)
    git(args.policy_repo, 'clone', '--shared', '--no-checkout', '--',
        str(args.policy_repo.resolve()), str(output))
    git(output, 'sparse-checkout', 'set', '--no-cone', '/.ai/', '/AGENTS.md',
        '/CLAUDE.md', '/ai-review-config.json')
    git(output, 'checkout', '--detach', policy)
    objects = git(args.source_repo, 'rev-parse', '--path-format=absolute',
                  '--git-path', 'objects').decode().strip()
    with (output / '.git/objects/info/alternates').open('a') as stream:
        stream.write(objects + '\n')
    with tempfile.TemporaryDirectory(prefix='wc-review-index-') as temp:
        env = dict(ENV, GIT_INDEX_FILE=str(Path(temp) / 'index'))
        git(output, 'read-tree', pins['woocommerce']['base'], env=env)
        # Apply only the intended AGENTS policy change to historical instructions.
        # This retains unrelated historical instructions rather than replacing them.
        if agents_policy:
            git(output, 'apply', '--cached', data=agents_policy, env=env)
        agents = git(output, 'show', ':AGENTS.md', env=env) + NOTE.encode()

        def add(path, content, mode='100644'):
            blob = git(output, 'hash-object', '-w', '--stdin', '--no-filters',
                       data=content).decode().strip()
            git(output, 'update-index', '--add', '--cacheinfo', mode, blob,
                path, env=env)

        add('AGENTS.md', agents)
        provenance = {'dependency_snapshots': [], 'external_consumer': consumer}
        for dep in pins['dependency_snapshots']:
            manifest = dict(dep, files=[])
            tree = git(args.adapter_repo, 'ls-tree', '-r', '-z', dep['commit'])
            for entry in tree.split(b'\0'):
                if not entry:
                    continue
                meta, raw_path = entry.split(b'\t', 1)
                mode, kind, blob = meta.decode().split()
                path = raw_path.decode()
                if kind != 'blob':
                    raise ValueError('Dependency submodules are not supported')
                if mode == '120000':
                    raise ValueError('Dependency symlinks are not supported')
                content = git(args.adapter_repo, 'cat-file', 'blob', blob)
                add(dep['directory'] + '/' + path, content, mode)
                manifest['files'].append({'path': path, 'blob': blob,
                                          'sha256': hashlib.sha256(content).hexdigest()})
            provenance['dependency_snapshots'].append(manifest)
        add(consumer['destination'], stripe)
        add('eval-context/source-provenance.json',
            (json.dumps(provenance, indent=2) + '\n').encode())
        tree = git(output, 'write-tree', env=env).decode().strip()
        identity = dict(ENV, GIT_AUTHOR_NAME='Fixture preparation',
                        GIT_AUTHOR_EMAIL='fixtures@example.invalid',
                        GIT_COMMITTER_NAME='Fixture preparation',
                        GIT_COMMITTER_EMAIL='fixtures@example.invalid')
        source = git(output, 'commit-tree', tree, '-m', 'Historical review source',
                     env=identity).decode().strip()
    git(output, 'update-ref', 'refs/heads/review-fixture-source', source)
    # The native runner excludes this registered suite (including these answers).
    target = output / SUITE
    for name in PATCHES:
        patch = target / GROUP / name
        patch.parent.mkdir(parents=True, exist_ok=True)
        patch.write_bytes((PACKAGE / name).read_bytes())
    cases = json.loads((target / 'cases.json').read_text())
    template = json.loads((PACKAGE / 'cases.template.json').read_text())
    additions = template['cases']
    ids = {case['id'] for case in cases['cases']}
    for case in additions:
        if case['id'] in ids:
            raise ValueError('Case ID already registered: ' + case['id'])
        case['base_commit'] = source
        cases['cases'].append(case)
    groups = cases.setdefault('groups', [])
    for group in template['groups']:
        if group['id'] in {existing['id'] for existing in groups}:
            raise ValueError('Group ID already registered: ' + group['id'])
        groups.append(group)
    (target / 'cases.json').write_text(json.dumps(cases, indent=2) + '\n')
    print(json.dumps({'output': str(output), 'policy_commit': policy,
                      'source_commit': source, 'source_tree': tree,
                      'agents_policy_sha256': agents_policy_sha256,
                      'cases': [case['id'] for case in additions]}, indent=2))


if __name__ == '__main__':
    main()
