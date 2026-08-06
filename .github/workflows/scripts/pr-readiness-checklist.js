'use strict';

const MARKER_PREFIX = '<!-- pr-readiness-summary';

const TASKS = [
    {
        label: 'Lint',
        matches: (name) => name.startsWith('Lint - '),
        remediation: 'See the inline annotations on this PR for details.',
    },
    {
        label: 'PHPStan',
        matches: (name) => name === 'PHPStan Analysis' || name.startsWith('PHPStan: PHP'),
        remediation: 'See the inline annotations on this PR for details.',
    },
    {
        label: 'Unit tests (PHP)',
        matches: (name) => name.includes('[unit:php]'),
        remediation:
            'See the failing job for details. [Guide to writing unit tests](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/README.md#guide-for-writing-unit-tests).',
    },
    {
        label: 'E2E tests',
        matches: (name) => name.includes('[e2e]'),
        remediation:
            'See the failing job for details. [Guide to writing E2E tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e#writing-e2e-tests).',
    },
    {
        label: 'API tests',
        matches: (name) => name.includes('[api]'),
        remediation:
            'See the failing job for details. [API tests](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/e2e/tests/api-tests).',
    },
    {
        label: 'Changelog entry',
        matches: (name) => name === 'Validate changelog',
        remediation:
            "Run `pnpm --filter=<project> changelog add`, or check the 'no changelog needed' box in the PR description with a comment.",
    },
    {
        label: 'Milestone',
        matches: (name) => name === 'Ensure milestone is or will be assigned',
        remediation:
            'Check the auto-assign box in the PR description, or select a milestone manually.',
    },
    {
        label: 'Dependency versions',
        matches: (name) => name === 'Validate dependencies version',
        remediation:
            'Run `pnpm syncpack fix-mismatches` (or update manually) and commit the result.',
    },
    {
        label: 'Markdown lint',
        matches: (name) => name === 'Validate markdown',
        remediation: 'See the failing job output for details.',
    },
];

const MAX_JOB_LINKS_PER_TASK = 2;

// The workflow that produces most of the checks above. Reporting an all-clear
// without it having run is the difference between "everything passed" and
// "nothing ran yet".
const CI_WORKFLOW_NAME = 'CI';

// Whether the CI workflow actually produced results for a SHA, given its
// workflow run (or undefined if none exists yet).
//
// `classifyCheckRuns` cannot answer this, because check-run *absence* is
// ambiguous: a check run that has not been created yet, one belonging to a
// workflow skipped by path filters, and one from a workflow sitting at
// `action_required` awaiting maintainer approval all look identical - each
// simply leaves no entry, and so is treated as "not applicable" rather than
// "not decided". On a first-time contributor's fork PR, `CI` is held at
// `action_required` and creates no check runs at all, so the classifier sees
// only the fast `pull_request_target` milestone check and concludes that
// everything passes, on a PR where nothing has run. That is exactly the
// population this bot exists to serve, so the all-clear path needs positive
// evidence rather than an inference drawn from absence.
//
// `status === 'completed'` matters as much as the conclusion: mid-run, CI's
// jobs create their check runs only as they start, so an in-progress CI can
// still leave a clear-looking, entirely empty checklist a minute after the
// PR is opened.
function ciHasProducedResults(ciRun) {
    return (
        Boolean(ciRun) &&
        ciRun.status === 'completed' &&
        ciRun.conclusion !== 'action_required'
    );
}

// Returns { tasks, hasPending }. `tasks` holds only decided (pass/fail)
// tasks - a task with zero matching runs ("not applicable") and a task
// still in_progress ("not decided yet") both leave no entry in `tasks`,
// but only the latter also sets `hasPending`. That distinction matters:
// three separate workflows (CI, PHPStan Analysis, Require milestone) each
// trigger their own workflow_run independently, so it's normal for e.g.
// PHPStan and Milestone to complete (and pass) minutes before CI's
// Lint/Unit/E2E/API jobs do. Without `hasPending`, computeOverallState
// would see only the two decided, passing tasks and call it "clear" -
// announcing all-clear while most checks haven't even run yet.
function classifyCheckRuns(checkRuns) {
    let hasPending = false;

    const tasks = TASKS.map((task) => {
        const matching = checkRuns.filter(
            (run) => task.matches(run.name) && !run.name.endsWith(' (optional)')
        );
        const relevant = matching.filter((run) => run.conclusion !== 'skipped');

        if (relevant.length === 0) {
            return null;
        }

        const undecided = relevant.some((run) => run.status !== 'completed');

        if (undecided) {
            hasPending = true;
            // If some runs have already completed and failed, surface the failure
            // immediately while other sibling runs are still in progress. The
            // comment will update once all runs settle, but actionable failures
            // are not hidden.
            const completedFailingRuns = relevant.filter(
                (run) => run.status === 'completed' && run.conclusion !== 'success'
            );
            if (completedFailingRuns.length > 0) {
                const result = {
                    label: task.label,
                    status: 'fail',
                    remediation: task.remediation,
                    jobUrls: completedFailingRuns
                        .slice(0, MAX_JOB_LINKS_PER_TASK)
                        .map((run) => run.html_url),
                };
                return result;
            }
            // No completed failures yet, just pending.
            return null;
        }

        const failingRuns = relevant.filter((run) => run.conclusion !== 'success');

        const failing = failingRuns.length > 0;

        const result = {
            label: task.label,
            status: failing ? 'fail' : 'pass',
            remediation: task.remediation,
        };

        if (failing) {
            // html_url is generated deterministically by GitHub from
            // repo/run/job ids, never from PR-authored content, so unlike
            // annotation text it's safe to link directly with no
            // sanitization.
            result.jobUrls = failingRuns
                .slice(0, MAX_JOB_LINKS_PER_TASK)
                .map((run) => run.html_url);
        }

        return result;
    }).filter(Boolean);

    return { tasks, hasPending };
}

function computeOverallState(tasks) {
    return tasks.some((task) => task.status === 'fail') ? 'failing' : 'clear';
}

function parsePreviousState(commentBody) {
    if (!commentBody) {
        return null;
    }
    const match = commentBody.match(
        /<!-- pr-readiness-summary status=(failing|clear) -->/
    );
    return match ? match[1] : null;
}

const HEADER = '## PR Readiness Checks';

// Mention-worthy transitions: the ones a real state change happened on.
// These are the only ones that also flip the returned `mentioned` flag.
const TRANSITION_MESSAGES = {
    'none->failing': (authorLogin) =>
        `Thanks for the PR, @${authorLogin}! A few things need attention before this can be reviewed:`,
    'none->clear': (authorLogin) =>
        `Thanks for your contribution, @${authorLogin}! Everything's passing — we'll take a look soon.`,
    'failing->clear': (authorLogin) =>
        `All checks are passing now, @${authorLogin} — thanks for addressing the feedback!`,
    'clear->failing': (authorLogin) =>
        `Heads up @${authorLogin} — a new push introduced some failures that need attention:`,
};

// Same-state re-runs: the comment still updates silently (no new mention),
// but the body always carries an author-anchored line so a reader dropping
// into the comment mid-thread (or an edit notification, however rare)
// still has that context - the checklist is never shown bare.
//
// `clear->clear` is deliberately absent, and this is the one entry whose
// absence is load-bearing rather than cosmetic: the orchestrator returns
// before calling buildCommentBody on that transition (see the
// `previousState === 'clear' && overallState === 'clear'` guard in
// manage-pr-readiness-comment.js), so an entry here could never reach a
// comment. If that guard is ever relaxed, add the message back at the same
// time - every transition that reaches this point must resolve to an intro
// line, and there is no fallback.
const SILENT_STATUS_MESSAGES = {
    'failing->failing': (authorLogin) =>
        `Hi @${authorLogin}, here's the current status — a few things still need attention:`,
};

// GitHub only sends a mention notification when a comment is *created*,
// never when an existing one is edited - so editing the sticky checklist
// comment in place, however its intro line reads, never actually notifies
// anyone past the very first time it's created. A transition listed here
// gets a short, separate comment (a real `createComment` call) instead, so
// the author is actually notified when it matters.
//
// `none->failing` and `none->clear` are deliberately absent: those are the
// sticky comment's own first-ever creation, which already notifies on its
// own - a second comment right after would just be a redundant duplicate.
// `failing->clear` is an ordinary successful re-run, not something that
// needs to interrupt the author. `failing->failing` is a repeated failure,
// already covered by "no re-ping while still failing". `clear->clear` never
// reaches this function at all (see SILENT_STATUS_MESSAGES above).
const PING_MESSAGES = {
    'clear->failing': (authorLogin, stickyCommentUrl) =>
        `@${authorLogin}, the readiness checklist now has failures — see [the checklist above](${stickyCommentUrl}).`,
};

function buildCommentBody({ tasks, previousState, authorLogin, stickyCommentUrl }) {
    const overallState = computeOverallState(tasks);
    const transitionKey = `${previousState || 'none'}->${overallState}`;
    const mentionMessage = TRANSITION_MESSAGES[transitionKey];
    const introMessage = mentionMessage || SILENT_STATUS_MESSAGES[transitionKey];
    const pingMessage = PING_MESSAGES[transitionKey];

    const lines = [
        `${MARKER_PREFIX} status=${overallState} -->`,
        '',
        HEADER,
        '',
        introMessage(authorLogin),
        '',
    ];

    if (overallState === 'clear') {
        lines.push('🟢 All checks are passing.');
    } else {
        lines.push(
            ...tasks
                .filter((task) => task.status === 'fail')
                .flatMap((task, index) => {
                    const jobLinks =
                        task.jobUrls && task.jobUrls.length > 0
                            ? ' ' +
                              task.jobUrls
                                  .map((url, index) =>
                                      task.jobUrls.length > 1
                                          ? `[Job ${index + 1}](${url})`
                                          : `[Job](${url})`
                                  )
                                  .join(', ')
                            : '';
                    const statusLine = `🔴 ${task.label}${jobLinks}`;
                    // Remediation text (and any non-CI link it carries, e.g. a
                    // guide) goes on its own indented line, kept separate from
                    // the CI job links on the status line above.
                    const block = [statusLine, `- ${task.remediation}`];
                    // The remediation line opens a Markdown list, and a status
                    // line following it without a blank line in between is
                    // lazy continuation - it gets absorbed into that list item
                    // rather than starting its own block. Separating the
                    // blocks keeps each failure standing on its own.
                    return index === 0 ? block : ['', ...block];
                })
        );
    }

    return {
        body: lines.join('\n'),
        mentioned: Boolean(mentionMessage),
        pingBody: pingMessage ? pingMessage(authorLogin, stickyCommentUrl) : null,
    };
}

module.exports = {
    MARKER_PREFIX,
    TASKS,
    CI_WORKFLOW_NAME,
    ciHasProducedResults,
    classifyCheckRuns,
    computeOverallState,
    parsePreviousState,
    buildCommentBody,
};
