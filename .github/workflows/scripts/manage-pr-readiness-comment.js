'use strict';

const {
    MARKER_PREFIX,
    classifyCheckRuns,
    computeOverallState,
    parsePreviousState,
    buildCommentBody,
} = require('./pr-readiness-checklist');

async function findExistingComment(github, context, prNumber) {
    const comments = await github.paginate(github.rest.issues.listComments, {
        owner: context.repo.owner,
        repo: context.repo.repo,
        issue_number: prNumber,
        per_page: 100,
    });

    return (
        comments.find(
            (comment) =>
                comment.user.login === 'github-actions[bot]' &&
                comment.body.includes(MARKER_PREFIX)
        ) || null
    );
}

const MAX_ANNOTATIONS_PER_TASK = 5;
const MAX_ANNOTATION_TEXT_LENGTH = 200;

// Annotation path/message text originates from the PR author's own code
// (e.g. a PHPStan message quoting a class/method/variable name they chose),
// so it must never be embedded as live markdown - a crafted identifier
// could render as a link, image, raw HTML, or an @mention of an unrelated
// user. Collapsing to one line, stripping backticks, and capping length
// makes the string safe to wrap in a single inline code span, which GitHub
// renders as inert literal text with no markdown/HTML interpretation.
function sanitizeAnnotationText(text) {
    return String(text)
        .replace(/\s+/g, ' ')
        .replace(/`/g, "'")
        .trim()
        .slice(0, MAX_ANNOTATION_TEXT_LENGTH);
}

// For tasks marked `annotatable` in pr-readiness-checklist.js, pull a few
// real check-run annotations (e.g. PHPStan's file/line/message) so the
// checklist carries actionable detail instead of only "see the job".
// Best-effort: a fetch failure just leaves the task without `details`.
async function attachAnnotationDetails(github, context, tasks, core) {
    return Promise.all(
        tasks.map(async (task) => {
            const { checkRunIds, ...rest } = task;
            if (task.status !== 'fail' || !checkRunIds || checkRunIds.length === 0) {
                return rest;
            }

            try {
                const annotationLists = await Promise.all(
                    checkRunIds.map((checkRunId) =>
                        github.paginate(github.rest.checks.listAnnotations, {
                            owner: context.repo.owner,
                            repo: context.repo.repo,
                            check_run_id: checkRunId,
                            per_page: 100,
                        })
                    )
                );

                const details = annotationLists
                    .flat()
                    .filter((annotation) => annotation.annotation_level === 'failure')
                    .slice(0, MAX_ANNOTATIONS_PER_TASK)
                    .map((annotation) => {
                        const path = sanitizeAnnotationText(annotation.path);
                        const line = sanitizeAnnotationText(annotation.start_line);
                        const message = sanitizeAnnotationText(annotation.message);
                        return `\`${path}:${line} — ${message}\``;
                    });

                return details.length > 0 ? { ...rest, details } : rest;
            } catch (error) {
                core.warning(
                    `Failed to fetch annotations for ${task.label}: ${error.message}`
                );
                return rest;
            }
        })
    );
}

async function resolvePullRequest(github, context) {
    const { head_sha: headSha, head_repository: headRepository } =
        context.payload.workflow_run;

    // Query the commit's own repo (the fork, for a fork PR), not the base
    // repo: GitHub's commit->PR association index has been observed to lag
    // significantly for the base repo on a commit that only exists on a
    // fork, while the same query against the fork itself resolves
    // immediately. `head_repository` is populated on `workflow_run` even
    // when `pull_requests` is emptied for forks, so it's always available.
    const sourceOwner = headRepository ? headRepository.owner.login : context.repo.owner;
    const sourceRepo = headRepository ? headRepository.name : context.repo.repo;

    const { data: associated } =
        await github.rest.repos.listPullRequestsAssociatedWithCommit({
            owner: sourceOwner,
            repo: sourceRepo,
            commit_sha: headSha,
        });

    return associated.find((pr) => pr.head.sha === headSha) || null;
}

module.exports = async ({ github, context, core }) => {
    const triggeringEvent = context.payload.workflow_run.event;
    if (!['pull_request', 'pull_request_target'].includes(triggeringEvent)) {
        core.info(`Ignoring workflow_run for event '${triggeringEvent}'.`);
        return;
    }

    const pr = await resolvePullRequest(github, context);
    if (!pr) {
        core.info('No open pull request found for this commit.');
        return;
    }

    if (pr.draft) {
        core.info('Pull request is a draft, skipping.');
        return;
    }

    if (!pr.head.repo || pr.head.repo.full_name === pr.base.repo.full_name) {
        core.info('Pull request is not from a fork, skipping.');
        return;
    }

    let checkRuns;
    try {
        checkRuns = await github.paginate(
            github.rest.checks.listForRef,
            {
                owner: context.repo.owner,
                repo: context.repo.repo,
                ref: pr.head.sha,
                per_page: 100,
            },
            (response) => response.data.check_runs
        );
    } catch (error) {
        core.warning(`Failed to fetch check-runs for ${pr.head.sha}: ${error.message}`);
        return;
    }

    const tasks = await attachAnnotationDetails(
        github,
        context,
        classifyCheckRuns(checkRuns),
        core
    );

    const existingComment = await findExistingComment(github, context, pr.number);
    const previousState = parsePreviousState(
        existingComment && existingComment.body
    );

    if (tasks.length === 0 && !existingComment) {
        core.info(
            'No applicable checks yet and no existing comment; nothing to do.'
        );
        return;
    }

    const overallState = computeOverallState(tasks);

    if (existingComment && previousState === 'clear' && overallState === 'clear') {
        core.info(
            `Readiness state unchanged for #${pr.number}; skipping comment update.`
        );
        return;
    }

    const { body } = buildCommentBody({
        tasks,
        previousState,
        authorLogin: pr.user.login,
    });

    if (existingComment) {
        await github.rest.issues.updateComment({
            owner: context.repo.owner,
            repo: context.repo.repo,
            comment_id: existingComment.id,
            body,
        });
        core.info(`Updated PR readiness comment on #${pr.number}.`);
    } else {
        await github.rest.issues.createComment({
            owner: context.repo.owner,
            repo: context.repo.repo,
            issue_number: pr.number,
            body,
        });
        core.info(`Created PR readiness comment on #${pr.number}.`);
    }
};
