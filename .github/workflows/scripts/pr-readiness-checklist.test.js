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

test('classifyCheckRuns: a failing task (any label) carries its job url', () => {
    const tasks = classifyCheckRuns([
        checkRun('Lint - @woocommerce/plugin-woocommerce', {
            conclusion: 'failure',
            html_url: 'https://github.com/woocommerce/woocommerce/actions/runs/1/job/1',
        }),
    ]);
    assert.equal(tasks.length, 1);
    assert.deepEqual(tasks[0].jobUrls, [
        'https://github.com/woocommerce/woocommerce/actions/runs/1/job/1',
    ]);
});

test('classifyCheckRuns: job urls are capped at 2 even with more failing runs', () => {
    const tasks = classifyCheckRuns([
        checkRun('Unit Tests - A [unit:php]', { conclusion: 'failure', html_url: 'url-a' }),
        checkRun('Unit Tests - B [unit:php]', { conclusion: 'failure', html_url: 'url-b' }),
        checkRun('Unit Tests - C [unit:php]', { conclusion: 'failure', html_url: 'url-c' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.deepEqual(tasks[0].jobUrls, ['url-a', 'url-b']);
});

test('classifyCheckRuns: a passing task carries no job urls', () => {
    const tasks = classifyCheckRuns([
        checkRun('Lint - @woocommerce/plugin-woocommerce', { html_url: 'url-a' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].jobUrls, undefined);
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

test('buildCommentBody: first-ever comment with failures mentions the author, no separate ping', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [
            { label: 'Lint', status: 'fail', remediation: 'See annotations.' },
            { label: 'Milestone', status: 'pass', remediation: 'n/a' },
        ],
        previousState: null,
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(body.includes('<!-- pr-readiness-summary status=failing -->'));
    assert.ok(body.includes('## PR Readiness Checks'));
    assert.ok(body.includes('Thanks for the PR, @octocat!'));
    assert.ok(body.includes('❌ **Lint** — See annotations.'));
    assert.ok(body.includes('✅ **Milestone**'));
    assert.ok(!body.includes('✅ **Milestone** —'));
    // The comment creation itself already notifies; a second ping would be
    // a redundant duplicate for the very first comment on the PR.
    assert.equal(pingBody, null);
});

test('buildCommentBody: first-ever comment with everything passing thanks the author, no ping', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: null,
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(body.includes('<!-- pr-readiness-summary status=clear -->'));
    assert.ok(body.includes('## PR Readiness Checks'));
    assert.ok(body.includes("Thanks for your contribution, @octocat!"));
    assert.ok(body.includes('✅ All checks are passing.'));
    assert.equal(pingBody, null);
});

test('buildCommentBody: still failing does not re-mention, no ping, keeps header and author line', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'fail', remediation: 'See annotations.' }],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, false);
    assert.ok(body.includes('## PR Readiness Checks'));
    assert.ok(body.includes('@octocat'));
    assert.ok(body.includes("here's the current status"));
    assert.ok(!body.includes('Thanks for the PR'));
    assert.equal(pingBody, null);
});

test('buildCommentBody: fixed from failing to clear mentions the author, no ping (ordinary success)', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, true);
    assert.ok(body.includes('## PR Readiness Checks'));
    assert.ok(body.includes('All checks are passing now, @octocat'));
    assert.equal(pingBody, null);
});

test('buildCommentBody: still clear does not re-mention, no ping, keeps header and author line', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: 'clear',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, false);
    assert.ok(body.includes('## PR Readiness Checks'));
    assert.ok(body.includes('@octocat, still all green here.'));
    assert.equal(pingBody, null);
});

test('buildCommentBody: a failing task with one job url renders a single Job link', () => {
    const { body } = buildCommentBody({
        tasks: [
            {
                label: 'Lint',
                status: 'fail',
                remediation: 'See annotations.',
                jobUrls: ['https://example.com/job/1'],
            },
        ],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.ok(
        body.includes(
            '❌ **Lint** — See annotations. [Job](https://example.com/job/1)'
        )
    );
});

test('buildCommentBody: a failing task with multiple job urls numbers each link', () => {
    const { body } = buildCommentBody({
        tasks: [
            {
                label: 'Unit tests (PHP)',
                status: 'fail',
                remediation: 'See the failing job.',
                jobUrls: ['https://example.com/job/1', 'https://example.com/job/2'],
            },
        ],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.ok(
        body.includes(
            '[Job 1](https://example.com/job/1), [Job 2](https://example.com/job/2)'
        )
    );
});

test('buildCommentBody: a passing task with job urls (should not happen) renders no link', () => {
    const { body } = buildCommentBody({
        tasks: [
            { label: 'Lint', status: 'fail', remediation: 'See annotations.' },
            {
                label: 'PHPStan',
                status: 'pass',
                remediation: 'n/a',
                jobUrls: ['https://example.com/job/1'],
            },
        ],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.ok(!body.includes('[Job]'));
});

test('buildCommentBody: regression from clear to failing mentions the author and pings separately', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'fail', remediation: 'See annotations.' }],
        previousState: 'clear',
        authorLogin: 'octocat',
        stickyCommentUrl: 'https://github.com/owner/repo/pull/1#issuecomment-123',
    });

    assert.equal(mentioned, true);
    assert.ok(
        body.includes('Heads up @octocat — a new push introduced some failures')
    );
    // A comment already exists (state was previously clear), so editing it
    // alone would notify no one - this is the one transition that needs a
    // real, separate createComment call. It links straight to the sticky
    // comment (its html_url, already fetched, no extra API call, and safe
    // to embed directly - GitHub-generated, not PR-author-controlled).
    assert.equal(
        pingBody,
        '@octocat, the readiness checklist now has failures — see [the checklist above](https://github.com/owner/repo/pull/1#issuecomment-123).'
    );
});
