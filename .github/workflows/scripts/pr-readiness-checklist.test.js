const assert = require('node:assert/strict');
const test = require('node:test');

const {
    classifyCheckRuns,
    computeOverallState,
    parsePreviousState,
    buildCommentBody,
} = require('./pr-readiness-checklist');

function checkRun(name, overrides = {}) {
    return {
        name,
        status: 'completed',
        conclusion: 'success',
        ...overrides,
    };
}

test('classifyCheckRuns: task is omitted when no check-run matches it', () => {
    const tasks = classifyCheckRuns([checkRun('Some Unrelated Check')]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: task passes when its only matching run succeeded', () => {
    const tasks = classifyCheckRuns([checkRun('Validate changelog')]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Changelog entry');
    assert.equal(tasks[0].status, 'pass');
});

test('classifyCheckRuns: task fails when any matching run failed', () => {
    const tasks = classifyCheckRuns([
        checkRun('Lint - @woocommerce/plugin-woocommerce'),
        checkRun('Lint - @woocommerce/blocks', { conclusion: 'failure' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Lint');
    assert.equal(tasks[0].status, 'fail');
});

test('classifyCheckRuns: skipped runs are treated as not applicable, not failing', () => {
    const tasks = classifyCheckRuns([
        checkRun('Validate markdown', { conclusion: 'skipped' }),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: still-running matching runs omit the task for this update', () => {
    const tasks = classifyCheckRuns([
        checkRun('PHPStan Analysis', { status: 'in_progress', conclusion: null }),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: matches dynamic test job names by bracketed testType', () => {
    const tasks = classifyCheckRuns([
        checkRun('Unit Tests - @woocommerce/plugin-woocommerce [unit:php]'),
        checkRun('E2E Tests - @woocommerce/plugin-woocommerce [e2e]', { conclusion: 'failure' }),
        checkRun('API Tests - @woocommerce/plugin-woocommerce [api]'),
    ]);
    const labels = tasks.map((task) => task.label).sort();
    assert.deepEqual(labels, ['API tests', 'E2E tests', 'Unit tests (PHP)']);
    assert.equal(
        tasks.find((task) => task.label === 'E2E tests').status,
        'fail'
    );
});

test('classifyCheckRuns: performance tests are not tracked', () => {
    const tasks = classifyCheckRuns([
        checkRun('Performance Tests - @woocommerce/plugin-woocommerce [performance]'),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: PHPStan via test-matrix jobs with "PHPStan: PHP" prefix are classified as PHPStan task', () => {
    const tasks = classifyCheckRuns([
        checkRun('PHPStan: PHP 7.4 - @woocommerce/email-editor-config [static:analysis]', { conclusion: 'failure' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'PHPStan');
    assert.equal(tasks[0].status, 'fail');
});

test('classifyCheckRuns: per-package PHPStan jobs do not match Unit tests (PHP) task', () => {
    const tasks = classifyCheckRuns([
        checkRun('PHPStan: PHP 8.4 - @woocommerce/email-editor-config [static:analysis]', { conclusion: 'failure' }),
    ]);
    // Should only match PHPStan, not Unit tests (PHP), since testType is [static:analysis] not [unit:php]
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'PHPStan');
});

test('classifyCheckRuns: a failing optional job does not flip the task to fail', () => {
    const tasks = classifyCheckRuns([
        checkRun('E2E Tests - @woocommerce/plugin-woocommerce [e2e]'),
        checkRun(
            'WP: pre-release - @woocommerce/plugin-woocommerce [e2e] (optional)',
            { conclusion: 'failure' }
        ),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'E2E tests');
    assert.equal(tasks[0].status, 'pass');
});

test('classifyCheckRuns: task is omitted when the only matching run is optional', () => {
    const tasks = classifyCheckRuns([
        checkRun(
            'WP: pre-release - @woocommerce/plugin-woocommerce [e2e] (optional)',
            { conclusion: 'failure' }
        ),
    ]);
    assert.deepEqual(tasks, []);
});

test('computeOverallState: clear when there are no applicable tasks', () => {
    assert.equal(computeOverallState([]), 'clear');
});

test('computeOverallState: clear when every applicable task passed', () => {
    assert.equal(computeOverallState([{ label: 'Lint', status: 'pass' }]), 'clear');
});

test('computeOverallState: failing when any applicable task failed', () => {
    assert.equal(
        computeOverallState([
            { label: 'Lint', status: 'pass' },
            { label: 'PHPStan', status: 'fail' },
        ]),
        'failing'
    );
});

test('parsePreviousState: reads the status embedded in the marker', () => {
    assert.equal(
        parsePreviousState('<!-- pr-readiness-summary status=failing -->\n\nsome text'),
        'failing'
    );
});

test('parsePreviousState: returns null for a comment with no marker', () => {
    assert.equal(parsePreviousState('just a regular comment'), null);
});

test('parsePreviousState: returns null for a missing comment', () => {
    assert.equal(parsePreviousState(undefined), null);
    assert.equal(parsePreviousState(null), null);
});

test('buildCommentBody: first-ever comment with failures mentions the author', () => {
    const { body, mentioned } = buildCommentBody({
        tasks: [
            { label: 'Lint', status: 'fail', remediation: 'See annotations.' },
            { label: 'Milestone', status: 'pass', remediation: 'n/a' },
        ],
        previousState: null,
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(body.includes('<!-- pr-readiness-summary status=failing -->'));
    assert.ok(body.includes('Thanks for the PR, @octocat!'));
    assert.ok(body.includes('❌ **Lint** — See annotations.'));
    assert.ok(body.includes('✅ **Milestone**'));
    assert.ok(!body.includes('✅ **Milestone** —'));
});

test('buildCommentBody: first-ever comment with everything passing thanks the author', () => {
    const { body, mentioned } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: null,
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(body.includes('<!-- pr-readiness-summary status=clear -->'));
    assert.ok(body.includes("Thanks for your contribution, @octocat!"));
    assert.ok(body.includes('✅ All checks are passing.'));
});

test('buildCommentBody: still failing does not re-mention the author', () => {
    const { body, mentioned } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'fail', remediation: 'See annotations.' }],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, false);
    assert.ok(!body.includes('@octocat'));
});

test('buildCommentBody: fixed from failing to clear mentions the author', () => {
    const { body, mentioned } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(body.includes('All checks are passing now, @octocat'));
});

test('buildCommentBody: still clear makes no change and does not mention', () => {
    const { body, mentioned } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: 'clear',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, false);
    assert.ok(!body.includes('@octocat'));
});

test('buildCommentBody: regression from clear to failing mentions the author', () => {
    const { body, mentioned } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'fail', remediation: 'See annotations.' }],
        previousState: 'clear',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(
        body.includes('Heads up @octocat — a new push introduced some failures')
    );
});
