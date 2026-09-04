'use strict';

const MARKER_PREFIX = '<!-- pr-readiness-summary';

// Derived from MARKER_PREFIX so writer (buildCommentBody) and reader
// (parsePreviousState) can't drift apart: a hard-coded literal here would
// silently stop matching if the prefix ever changed, making every run look
// like a first comment and re-mention the author on each push. None of the
// prefix's characters are regex metacharacters, so no escaping is needed.
const STATUS_MARKER_PATTERN = new RegExp(
    `${MARKER_PREFIX} status=(failing|clear) -->`
);

// Each remediation duplicates guidance that already lives next to the
// check it describes; the `// Source:` comment on each names that origin.
// When the origin changes its guidance, update the remediation with it -
// these strings are what community contributors act on, so a drifted one
// (a command that no longer exists, a moved doc) is worse than none.
//
// There is deliberately no "Unit tests (JS)" row. The CI job matrix
// (tools/monorepo-utils/src/ci-jobs) has no `unit:js` testType; a test
// job that doesn't declare one defaults to plain `unit`, which is what
// the JS suites run as (e.g. "JavaScript - @woocommerce/admin-library
// [unit]"). Those `[unit]` jobs are left untracked here for now - add a
// row matching `[unit]` to change that.
const TASKS = [
    {
        label: 'Lint',
        matches: (name) => name.startsWith('Lint - '),
        // Source: ci.yml lint jobs annotate via cs2pr / problem matchers.
        remediation: 'See the inline annotations on this PR for details.',
    },
    {
        label: 'PHPStan',
        matches: (name) => name === 'PHPStan Analysis' || name.startsWith('PHPStan: PHP'),
        // Source: phpstan.yml emits inline `::error file=` annotations.
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
        // Source: .github/CONTRIBUTING.md ("create a change file..."). Lead
        // with the concrete core command - community contributors don't know
        // the monorepo's package names, so a bare `--filter=<project>`
        // placeholder isn't actionable on its own.
        remediation:
            "Run `pnpm --filter=@woocommerce/plugin-woocommerce changelog add` (for changes to WooCommerce core; for another package, pass its name to `--filter` — see the [contributing guidelines](https://github.com/woocommerce/woocommerce/blob/trunk/.github/CONTRIBUTING.md)). Alternatively, check the 'no changelog needed' box in the PR description with a comment explaining why.",
    },
    {
        label: 'Milestone',
        matches: (name) => name === 'Ensure milestone is or will be assigned',
        // Source: pr-require-milestone.yml / the PR template's milestone
        // section.
        remediation:
            'Check the auto-assign box in the PR description, or select a milestone manually.',
    },
    {
        label: 'Dependency versions',
        matches: (name) => name === 'Validate dependencies version',
        // Source: ci.yml validate-syncpack's 'Validate - prompt mitigation
        // on failed validation' step.
        remediation:
            'Update the pinned version in `.syncpackrc` or `pnpm-workspace.yaml`, then run `pnpm sync-dependencies` and commit the result.',
    },
    {
        label: 'Markdown lint',
        matches: (name) => name === 'Validate markdown',
        remediation: 'See the failing job output for details.',
    },
];

const MAX_JOB_LINKS_PER_TASK = 2;

// Conclusions that mean the contributor has something to fix. Anything
// completed with another conclusion is ignored like `skipped`: `cancelled`
// (a maintainer cancelled the run, or concurrency cancelled it on a
// re-push), `neutral` ("passed with warnings" for several apps), and
// `stale` are not the contributor's failures, and reporting them as red -
// with a ping, on a clear->failing flip - blames the author for something
// they did not do.
const FAILING_CONCLUSIONS = ['failure', 'timed_out', 'action_required'];
const IGNORED_CONCLUSIONS = ['skipped', 'cancelled', 'neutral', 'stale'];

// The workflow that produces most of the checks above. Reporting an all-clear
// without it having run is the difference between "everything passed" and
// "nothing ran yet". Keyed on the workflow file rather than its display
// name, so renaming the workflow can't silently break the lookup.
const CI_WORKFLOW_FILE = 'ci.yml';

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
        const relevant = matching.filter(
            (run) => !IGNORED_CONCLUSIONS.includes(run.conclusion)
        );

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
                (run) =>
                    run.status === 'completed' &&
                    FAILING_CONCLUSIONS.includes(run.conclusion)
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

        const failingRuns = relevant.filter((run) =>
            FAILING_CONCLUSIONS.includes(run.conclusion)
        );

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
    const match = commentBody.match(STATUS_MARKER_PATTERN);
    return match ? match[1] : null;
}

// The whole go/no-go decision for one workflow_run event, as a pure
// function of already-fetched data, so every branch is unit-testable and
// the orchestrator reduces to fetch -> decideAction -> dispatch. Returns
// { action: 'skip' | 'create' | 'update', reason, previousState }; `reason`
// is a short tag the orchestrator maps to a log line, and `previousState`
// is only meaningful when the action is 'create' or 'update'.
function decideAction({ pr, tasks, hasPending, ciRun, existingComment }) {
    // Draft PRs are skipped, and the state this leaves behind is handled
    // asymmetrically on purpose. Draft -> ready self-heals without help:
    // pr-require-milestone.yml runs on `ready_for_review` (gated on
    // `draft == false`), and its workflow_run retriggers this bot, so the
    // checklist appears shortly after the PR is marked ready - that timing
    // is load-bearing, not luck. Ready -> draft deliberately leaves any
    // existing comment in place: the checklist is still accurate for the
    // pushed commits, and deleting/redacting it on a state the author will
    // likely leave again isn't worth the churn.
    if (pr.draft) {
        return { action: 'skip', reason: 'draft' };
    }

    // The bot serves community (fork) PRs only.
    if (!pr.head.repo || pr.head.repo.full_name === pr.base.repo.full_name) {
        return { action: 'skip', reason: 'not-a-fork' };
    }

    // An empty checklist is not an all-clear, it is an empty one. A push
    // where every tracked check is skipped (a docs- or .github-only commit)
    // classifies to zero tasks, which computeOverallState calls 'clear' -
    // and against an existing 'failing' comment that would announce "all
    // checks are passing now" on a commit where nothing ran. Leave whatever
    // state the previous push established alone.
    if (tasks.length === 0) {
        return { action: 'skip', reason: 'no-applicable-checks' };
    }

    const overallState = computeOverallState(tasks);

    // Three separate workflows trigger this independently, so it's normal
    // for some to finish (and pass) while others - e.g. CI's slower
    // Lint/Unit/E2E/API jobs - are still running. Reporting "clear" from
    // only the tasks decided so far would be a false all-clear; wait for
    // a later trigger, once every task has actually settled. A real
    // failure already found among the decided tasks is reported right
    // away regardless - that's actionable now and shouldn't wait on
    // slower jobs.
    if (hasPending && overallState === 'clear') {
        return { action: 'skip', reason: 'pending-checks' };
    }

    // A clear checklist built entirely from checks that never ran is not an
    // all-clear, it is an empty one - and the two are indistinguishable from
    // check-run data alone (see ciHasProducedResults). Confirm CI actually
    // produced results for this SHA before saying everything passed. Only the
    // clear path is gated: a failure already found is actionable now and is
    // still reported immediately, even while sibling jobs are running.
    if (overallState === 'clear' && !ciHasProducedResults(ciRun)) {
        return { action: 'skip', reason: 'ci-not-run' };
    }

    const previousState = parsePreviousState(
        existingComment && existingComment.body
    );

    // No update on clear->clear, so the comment keeps its original "edited"
    // timestamp instead of churning on every green re-run.
    if (existingComment && previousState === 'clear' && overallState === 'clear') {
        return { action: 'skip', reason: 'still-clear' };
    }

    return {
        action: existingComment ? 'update' : 'create',
        reason: null,
        previousState,
    };
}

// Sentence case, per the WooCommerce copy guidelines and matching the
// workflow's own name ('PR readiness comment').
const HEADER = '## PR readiness checks';

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
// time. A transition missing from both maps falls back to a generic intro
// in buildCommentBody rather than crashing: an uncaught error here fails
// the workflow_run job, which attaches to nothing visible on the PR, so
// the bot would just silently stop from the contributor's point of view.
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
        `@${authorLogin}, some checks started failing after the latest push. See the [readiness checks above](${stickyCommentUrl}) for details and how to fix them.`,
};

function buildCommentBody({ tasks, previousState, authorLogin, stickyCommentUrl }) {
    const overallState = computeOverallState(tasks);
    const transitionKey = `${previousState || 'none'}->${overallState}`;
    const mentionMessage = TRANSITION_MESSAGES[transitionKey];
    const introMessage =
        mentionMessage ||
        SILENT_STATUS_MESSAGES[transitionKey] ||
        ((login) => `Hi @${login}, here's the current readiness status:`);
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
                                  .map((url, jobIndex) =>
                                      task.jobUrls.length > 1
                                          ? `[Job ${jobIndex + 1}](${url})`
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
    TRANSITION_MESSAGES,
    SILENT_STATUS_MESSAGES,
    CI_WORKFLOW_FILE,
    ciHasProducedResults,
    classifyCheckRuns,
    computeOverallState,
    parsePreviousState,
    decideAction,
    buildCommentBody,
};
