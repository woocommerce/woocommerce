const assert = require('node:assert/strict');
const test = require('node:test');

const {
    TRANSITION_MESSAGES,
    SILENT_STATUS_MESSAGES,
    ciHasProducedResults,
    classifyCheckRuns,
    computeOverallState,
    parsePreviousState,
    decideAction,
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
    const { tasks } = classifyCheckRuns([checkRun('Some Unrelated Check')]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: task passes when its only matching run succeeded', () => {
    const { tasks } = classifyCheckRuns([checkRun('Validate changelog')]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Changelog entry');
    assert.equal(tasks[0].status, 'pass');
});

test('classifyCheckRuns: task fails when any matching run failed', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('Lint - @woocommerce/plugin-woocommerce'),
        checkRun('Lint - @woocommerce/blocks', { conclusion: 'failure' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Lint');
    assert.equal(tasks[0].status, 'fail');
});

test('classifyCheckRuns: skipped runs are treated as not applicable, not failing', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('Validate markdown', { conclusion: 'skipped' }),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: skipped matrix jobs with uninterpolated names are ignored', () => {
    // When a matrix job is skipped wholesale, GitHub creates its check run
    // with the un-evaluated expression as the name. The first one below
    // satisfies startsWith('Lint - ') and does NOT end with ' (optional)',
    // so only the ignored-conclusion filter keeps it out of the checklist -
    // this pins that, so reordering the filters (or matching on something
    // other than conclusion) can't quietly turn it into a phantom red Lint
    // row on every PR with an empty lint matrix.
    const { tasks, hasPending } = classifyCheckRuns([
        checkRun(
            "Lint - ${{ matrix.projectName }} ${{ (((matrix.optional && ' (optional)')) || '') }}",
            { conclusion: 'skipped' }
        ),
        checkRun('matrix.name', { conclusion: 'skipped' }),
    ]);
    assert.deepEqual(tasks, []);
    assert.equal(hasPending, false);
});

test('classifyCheckRuns: cancelled runs are ignored, not blamed on the contributor', () => {
    // ci.yml runs with cancel-in-progress, and maintainers cancel runs by
    // hand; neither is the author's failure.
    const { tasks, hasPending } = classifyCheckRuns([
        checkRun('Validate markdown', { conclusion: 'cancelled' }),
    ]);
    assert.deepEqual(tasks, []);
    assert.equal(hasPending, false);
});

test('classifyCheckRuns: a cancelled sibling does not drag down a successful run', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('Lint - @woocommerce/plugin-woocommerce'),
        checkRun('Lint - @woocommerce/blocks', { conclusion: 'cancelled' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].status, 'pass');
});

test('classifyCheckRuns: neutral and stale conclusions are ignored like skipped', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('Validate changelog', { conclusion: 'neutral' }),
        checkRun('Validate markdown', { conclusion: 'stale' }),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: timed_out and action_required count as failures', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('Unit Tests - A [unit:php]', { conclusion: 'timed_out' }),
        checkRun('Validate changelog', { conclusion: 'action_required' }),
    ]);
    const statuses = tasks.map((task) => task.status);
    assert.deepEqual(statuses, ['fail', 'fail']);
});

test('classifyCheckRuns: still-running matching runs omit the task for this update and flag hasPending', () => {
    const { tasks, hasPending } = classifyCheckRuns([
        checkRun('PHPStan Analysis', { status: 'in_progress', conclusion: null }),
    ]);
    assert.deepEqual(tasks, []);
    assert.equal(hasPending, true);
});

test('classifyCheckRuns: a task with zero matching runs does not flag hasPending (not applicable, not pending)', () => {
    const { tasks, hasPending } = classifyCheckRuns([checkRun('Some Unrelated Check')]);
    assert.deepEqual(tasks, []);
    assert.equal(hasPending, false);
});

test('classifyCheckRuns: hasPending stays true even when other tasks are fully decided', () => {
    // The real-world case this guards against: PHPStan and Milestone
    // (separate, faster workflows) complete and pass while CI's slower
    // Lint job is still running - the decided tasks must not silently
    // imply "everything's fine".
    const { tasks, hasPending } = classifyCheckRuns([
        checkRun('PHPStan Analysis'),
        checkRun('Ensure milestone is or will be assigned'),
        checkRun('Lint - @woocommerce/plugin-woocommerce', {
            status: 'in_progress',
            conclusion: null,
        }),
    ]);
    assert.equal(hasPending, true);
    const labels = tasks.map((task) => task.label).sort();
    assert.deepEqual(labels, ['Milestone', 'PHPStan']);
    assert.ok(tasks.every((task) => task.status === 'pass'));
});

test('classifyCheckRuns: a decided failure surfaces alongside a still-pending task', () => {
    const { tasks, hasPending } = classifyCheckRuns([
        checkRun('Validate changelog', { conclusion: 'failure' }),
        checkRun('Lint - @woocommerce/plugin-woocommerce', {
            status: 'in_progress',
            conclusion: null,
        }),
    ]);
    assert.equal(hasPending, true);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'Changelog entry');
    assert.equal(tasks[0].status, 'fail');
});

test('classifyCheckRuns: a completed failing matrix job is surfaced even when a sibling is still running', () => {
    // E2E job 1 has completed and failed; E2E job 2 is still running.
    // The failure should surface immediately (actionable now) while hasPending
    // is true so the comment will update once job 2 settles.
    const { tasks, hasPending } = classifyCheckRuns([
        checkRun('E2E Tests - @woocommerce/plugin-woocommerce [e2e]', {
            conclusion: 'failure',
            html_url: 'https://github.com/woocommerce/woocommerce/actions/runs/1/job/1',
        }),
        checkRun('E2E Tests - @woocommerce/blocks [e2e]', {
            status: 'in_progress',
            conclusion: null,
        }),
    ]);
    assert.equal(hasPending, true);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'E2E tests');
    assert.equal(tasks[0].status, 'fail');
    assert.deepEqual(tasks[0].jobUrls, [
        'https://github.com/woocommerce/woocommerce/actions/runs/1/job/1',
    ]);
});

test('classifyCheckRuns: matches dynamic test job names by bracketed testType', () => {
    const { tasks } = classifyCheckRuns([
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
    const { tasks } = classifyCheckRuns([
        checkRun('Performance Tests - @woocommerce/plugin-woocommerce [performance]'),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: PHPStan via test-matrix jobs with "PHPStan: PHP" prefix are classified as PHPStan task', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('PHPStan: PHP 7.4 - @woocommerce/email-editor-config [static:analysis]', { conclusion: 'failure' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'PHPStan');
    assert.equal(tasks[0].status, 'fail');
});

test('classifyCheckRuns: per-package PHPStan jobs do not match Unit tests (PHP) task', () => {
    const { tasks } = classifyCheckRuns([
        checkRun('PHPStan: PHP 8.4 - @woocommerce/email-editor-config [static:analysis]', { conclusion: 'failure' }),
    ]);
    // Should only match PHPStan, not Unit tests (PHP), since testType is [static:analysis] not [unit:php]
    assert.equal(tasks.length, 1);
    assert.equal(tasks[0].label, 'PHPStan');
});

test('classifyCheckRuns: a failing optional job does not flip the task to fail', () => {
    const { tasks } = classifyCheckRuns([
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
    const { tasks } = classifyCheckRuns([
        checkRun(
            'WP: pre-release - @woocommerce/plugin-woocommerce [e2e] (optional)',
            { conclusion: 'failure' }
        ),
    ]);
    assert.deepEqual(tasks, []);
});

test('classifyCheckRuns: a failing task (any label) carries its job url', () => {
    const { tasks } = classifyCheckRuns([
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
    const { tasks } = classifyCheckRuns([
        checkRun('Unit Tests - A [unit:php]', { conclusion: 'failure', html_url: 'url-a' }),
        checkRun('Unit Tests - B [unit:php]', { conclusion: 'failure', html_url: 'url-b' }),
        checkRun('Unit Tests - C [unit:php]', { conclusion: 'failure', html_url: 'url-c' }),
    ]);
    assert.equal(tasks.length, 1);
    assert.deepEqual(tasks[0].jobUrls, ['url-a', 'url-b']);
});

test('classifyCheckRuns: a passing task carries no job urls', () => {
    const { tasks } = classifyCheckRuns([
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

test('parsePreviousState: round-trips the state buildCommentBody writes', () => {
    // Writer and reader must agree on the marker; this is the assertion
    // that catches them drifting apart (e.g. a renamed prefix), which
    // otherwise fails silently by re-greeting the author on every push.
    for (const [previousState, tasks] of [
        [null, [{ label: 'Lint', status: 'fail', remediation: 'x' }]],
        ['failing', [{ label: 'Lint', status: 'pass', remediation: 'x' }]],
    ]) {
        const { body } = buildCommentBody({
            tasks,
            previousState,
            authorLogin: 'octocat',
        });
        assert.equal(
            parsePreviousState(body),
            tasks[0].status === 'fail' ? 'failing' : 'clear'
        );
    }
});

test('parsePreviousState: returns null for a missing comment', () => {
    assert.equal(parsePreviousState(undefined), null);
    assert.equal(parsePreviousState(null), null);
});

test('ciHasProducedResults: no CI run for the SHA is not evidence of passing', () => {
    assert.equal(ciHasProducedResults(undefined), false);
});

test('ciHasProducedResults: CI awaiting maintainer approval has run nothing', () => {
    // A first-time contributor's fork PR. `CI` is created but held, and
    // produces no check runs at all, so the classifier sees only the fast
    // pull_request_target milestone check and looks clear.
    assert.equal(
        ciHasProducedResults({
            status: 'completed',
            conclusion: 'action_required',
        }),
        false
    );
});

test('ciHasProducedResults: in-progress CI has not created all its check runs yet', () => {
    // Jobs behind `needs:` create their check runs only as they start, so
    // mid-run the checklist can be empty and clear-looking.
    assert.equal(
        ciHasProducedResults({ status: 'in_progress', conclusion: null }),
        false
    );
});

test('ciHasProducedResults: queued CI has not created its check runs yet', () => {
    assert.equal(ciHasProducedResults({ status: 'queued', conclusion: null }), false);
});

test('ciHasProducedResults: a completed CI run is real evidence', () => {
    assert.equal(
        ciHasProducedResults({ status: 'completed', conclusion: 'success' }),
        true
    );
    // A failing CI run still counts as evidence: its check runs exist, so the
    // classifier can see them and will report the failures on their own.
    assert.equal(
        ciHasProducedResults({ status: 'completed', conclusion: 'failure' }),
        true
    );
});

// --- decideAction ---
// Fixtures mirror the fields the decision actually reads.

function pullRequest(overrides = {}) {
    return {
        number: 1,
        draft: false,
        head: { sha: 'abc123', repo: { full_name: 'contributor/woocommerce' } },
        base: { repo: { full_name: 'woocommerce/woocommerce' } },
        user: { login: 'octocat' },
        ...overrides,
    };
}

const COMPLETED_CI = { status: 'completed', conclusion: 'success' };
const FAIL_TASK = { label: 'Lint', status: 'fail', remediation: 'x' };
const PASS_TASK = { label: 'Lint', status: 'pass', remediation: 'x' };

function stickyComment(state) {
    return {
        id: 7,
        body: `<!-- pr-readiness-summary status=${state} -->`,
        html_url: 'https://example.com/comment/7',
    };
}

test('decideAction: skips draft PRs', () => {
    const decision = decideAction({
        pr: pullRequest({ draft: true }),
        tasks: [FAIL_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: null,
    });
    assert.deepEqual(decision, { action: 'skip', reason: 'draft' });
});

test('decideAction: skips PRs that are not from a fork', () => {
    const decision = decideAction({
        pr: pullRequest({
            head: {
                sha: 'abc123',
                repo: { full_name: 'woocommerce/woocommerce' },
            },
        }),
        tasks: [FAIL_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: null,
    });
    assert.deepEqual(decision, { action: 'skip', reason: 'not-a-fork' });
});

test('decideAction: a missing head repo is treated as not-a-fork, not a crash', () => {
    const decision = decideAction({
        pr: pullRequest({ head: { sha: 'abc123', repo: null } }),
        tasks: [FAIL_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: null,
    });
    assert.deepEqual(decision, { action: 'skip', reason: 'not-a-fork' });
});

test('decideAction: no applicable checks and no comment is a no-op', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: null,
    });
    assert.deepEqual(decision, {
        action: 'skip',
        reason: 'no-applicable-checks',
    });
});

test('decideAction: an all-skipped push never flips an existing failing comment to clear', () => {
    // The false-all-clear regression: a docs- or .github-only follow-up
    // commit skips every tracked check, classifying to zero tasks, which
    // computeOverallState calls 'clear'. Against a failing sticky comment
    // that must not become "all checks are passing now".
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: stickyComment('failing'),
    });
    assert.deepEqual(decision, {
        action: 'skip',
        reason: 'no-applicable-checks',
    });
});

test('decideAction: defers a clear verdict while sibling checks are still pending', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [PASS_TASK],
        hasPending: true,
        ciRun: COMPLETED_CI,
        existingComment: null,
    });
    assert.deepEqual(decision, { action: 'skip', reason: 'pending-checks' });
});

test('decideAction: defers a clear verdict without evidence that CI ran', () => {
    for (const ciRun of [
        undefined,
        { status: 'completed', conclusion: 'action_required' },
        { status: 'in_progress', conclusion: null },
    ]) {
        const decision = decideAction({
            pr: pullRequest(),
            tasks: [PASS_TASK],
            hasPending: false,
            ciRun,
            existingComment: null,
        });
        assert.deepEqual(decision, { action: 'skip', reason: 'ci-not-run' });
    }
});

test('decideAction: a failure is reported immediately, even pending siblings and no CI evidence', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [FAIL_TASK],
        hasPending: true,
        ciRun: undefined,
        existingComment: null,
    });
    assert.deepEqual(decision, {
        action: 'create',
        reason: null,
        previousState: null,
    });
});

test('decideAction: clear staying clear leaves the comment untouched', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [PASS_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: stickyComment('clear'),
    });
    assert.deepEqual(decision, { action: 'skip', reason: 'still-clear' });
});

test('decideAction: a clear run with CI evidence creates the first comment', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [PASS_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: null,
    });
    assert.deepEqual(decision, {
        action: 'create',
        reason: null,
        previousState: null,
    });
});

test('decideAction: fixed failures update the existing comment with the failing previous state', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [PASS_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: stickyComment('failing'),
    });
    assert.deepEqual(decision, {
        action: 'update',
        reason: null,
        previousState: 'failing',
    });
});

test('decideAction: a regression updates the existing comment with the clear previous state', () => {
    const decision = decideAction({
        pr: pullRequest(),
        tasks: [FAIL_TASK],
        hasPending: false,
        ciRun: COMPLETED_CI,
        existingComment: stickyComment('clear'),
    });
    assert.deepEqual(decision, {
        action: 'update',
        reason: null,
        previousState: 'clear',
    });
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
    assert.ok(body.includes('## PR readiness checks'));
    assert.ok(body.includes('Thanks for the PR, @octocat!'));
    assert.ok(body.includes('🔴 Lint'));
    assert.ok(body.includes('- See annotations.'));
    // Passing tasks are not shown in failing state.
    assert.ok(!body.includes('Milestone'));
    assert.ok(!body.includes('    - n/a'));
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
    assert.ok(body.includes('## PR readiness checks'));
    assert.ok(body.includes("Thanks for your contribution, @octocat!"));
    assert.ok(body.includes('🟢 All checks are passing.'));
    assert.equal(pingBody, null);
});

test('buildCommentBody: still failing does not re-mention, no ping, keeps header and author line', () => {
    const { body, mentioned, pingBody } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'fail', remediation: 'See annotations.' }],
        previousState: 'failing',
        authorLogin: 'octocat',
    });

    assert.equal(mentioned, false);
    assert.ok(body.includes('## PR readiness checks'));
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
    assert.ok(body.includes('## PR readiness checks'));
    assert.ok(body.includes('All checks are passing now, @octocat'));
    assert.equal(pingBody, null);
});

test('clear->clear defines no message, because the orchestrator never gets there', () => {
    // The orchestrator returns before calling buildCommentBody when a clear
    // PR stays clear, so any message defined for that transition would be
    // unreachable. Assert the absence directly on the maps: a message
    // re-added here would look reasonable in review and silently never
    // render.
    assert.equal(TRANSITION_MESSAGES['clear->clear'], undefined);
    assert.equal(SILENT_STATUS_MESSAGES['clear->clear'], undefined);
});

test('buildCommentBody: an unmapped transition falls back to a generic intro instead of crashing', () => {
    // An uncaught error would fail a workflow_run job nothing on the PR
    // links to - the bot would silently stop. The fallback keeps the
    // checklist rendering with a neutral, author-anchored line.
    const { body, mentioned } = buildCommentBody({
        tasks: [{ label: 'Lint', status: 'pass', remediation: 'n/a' }],
        previousState: 'clear',
        authorLogin: 'octocat',
    });
    assert.equal(mentioned, false);
    assert.ok(body.includes("Hi @octocat, here's the current readiness status:"));
    assert.ok(body.includes('🟢 All checks are passing.'));
});

test('buildCommentBody: a failing task with one job url renders a single Job link on the status line, remediation on its own line', () => {
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

    assert.ok(body.includes('🔴 Lint [Job](https://example.com/job/1)'));
    assert.ok(body.includes('- See annotations.'));
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

test('buildCommentBody: consecutive failing tasks are separated by a blank line', () => {
    const { body } = buildCommentBody({
        tasks: [
            {
                label: 'Lint',
                status: 'fail',
                remediation: 'See annotations.',
                jobUrls: ['https://example.com/job/1'],
            },
            {
                label: 'PHPStan',
                status: 'fail',
                remediation: 'See annotations.',
                jobUrls: ['https://example.com/job/2'],
            },
        ],
        previousState: 'clear',
        authorLogin: 'octocat',
    });

    // Without the blank line, Markdown lazy continuation folds the second
    // task's status line into the first task's remediation list item, so the
    // two failures render as one. Assert on the exact adjacency rather than
    // mere substring presence, which stays true even when it renders wrong.
    assert.ok(
        body.includes(
            '- See annotations.\n\n🔴 PHPStan [Job](https://example.com/job/2)'
        )
    );
});

test('buildCommentBody: passing tasks are not shown in failing state', () => {
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

    // Only failing task shown
    assert.ok(body.includes('🔴 Lint'));
    // Passing task not shown at all
    assert.ok(!body.includes('PHPStan'));
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
        '@octocat, some checks started failing after the latest push. See the [readiness checks above](https://github.com/owner/repo/pull/1#issuecomment-123) for details and how to fix them.'
    );
});
