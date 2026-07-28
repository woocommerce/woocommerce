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

function classifyCheckRuns(checkRuns) {
    return TASKS.map((task) => {
        const matching = checkRuns.filter((run) => task.matches(run.name));
        const relevant = matching.filter((run) => run.conclusion !== 'skipped');

        if (relevant.length === 0) {
            return null;
        }

        const undecided = relevant.some((run) => run.status !== 'completed');
        if (undecided) {
            return null;
        }

        const failing = relevant.some((run) => run.conclusion !== 'success');

        return {
            label: task.label,
            status: failing ? 'fail' : 'pass',
            remediation: task.remediation,
        };
    }).filter(Boolean);
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

function buildCommentBody({ tasks, previousState, authorLogin }) {
    const overallState = computeOverallState(tasks);
    const transitionKey = `${previousState || 'none'}->${overallState}`;
    const mentionMessage = TRANSITION_MESSAGES[transitionKey];

    const lines = [`${MARKER_PREFIX} status=${overallState} -->`];

    if (mentionMessage) {
        lines.push('', mentionMessage(authorLogin));
    }

    lines.push('');

    if (overallState === 'clear') {
        lines.push('✅ All checks are passing.');
    } else {
        lines.push(
            ...tasks.map(
                (task) =>
                    `- ${task.status === 'fail' ? '❌' : '✅'} **${task.label}**${
                        task.status === 'fail' ? ` — ${task.remediation}` : ''
                    }`
            )
        );
    }

    return { body: lines.join('\n'), mentioned: Boolean(mentionMessage) };
}

module.exports = {
    MARKER_PREFIX,
    TASKS,
    classifyCheckRuns,
    computeOverallState,
    parsePreviousState,
    buildCommentBody,
};
