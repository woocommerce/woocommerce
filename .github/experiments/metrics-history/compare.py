#!/usr/bin/env python3
"""Require equivalent source restoration and summarize three-arm phase timings."""
import argparse
import json
from pathlib import Path
import statistics

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('artifacts', type=Path)
args = parser.parse_args()
results = []
for arm in ['full', 'blobless', 'shallow']:
    for repetition in [1, 2, 3]:
        folder = args.artifacts / f'metrics-history-{arm}-{repetition}'
        data = json.loads((folder / 'result.json').read_text())
        assert data.get('success') is True, (arm, repetition, 'failed trial')
        assert data['arm'] == arm
        data['repetition'] = repetition
        results.append(data)
reference = results[0]
for result in results:
    for key in ['head', 'base', 'head_tree', 'base_tree', 'mixed_tree', 'base_lockfile_sha256', 'restored_resource_hashes']:
        assert result[key] == reference[key], (result['arm'], result['repetition'], key)
    if result['arm'] == 'shallow':
        assert result['shallow_after'] == 'true'
    else:
        assert result['shallow_after'] == reference['shallow_after']
summary = {'scope': 'History fetch plus baseline checkout and head test-resource restore; excludes install/build/browser execution', 'arms': {}}
for arm in ['full', 'blobless', 'shallow']:
    trials = [r for r in results if r['arm'] == arm]
    summary['arms'][arm] = {
        'history_fetch_seconds': [r['timings']['history_fetch'] for r in trials],
        'pipeline_seconds': [r['pipeline_seconds'] for r in trials],
        'pipeline_median_seconds': statistics.median(r['pipeline_seconds'] for r in trials),
        'packed_growth_bytes': [r['packed_growth'] for r in trials],
    }
for arm in ['blobless', 'shallow']:
    summary['arms'][arm]['median_saving_seconds'] = summary['arms']['full']['pipeline_median_seconds'] - summary['arms'][arm]['pipeline_median_seconds']
print(json.dumps(summary, indent=2))
