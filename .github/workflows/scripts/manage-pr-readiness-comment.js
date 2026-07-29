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

    const tasks = classifyCheckRuns(checkRuns);

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

    const { body, pingBody } = buildCommentBody({
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

    // Editing the sticky comment above never notifies anyone (GitHub only
    // notifies on comment creation), so a transition that actually needs
    // the author's attention gets its own short, separate comment here.
    if (pingBody) {
        await github.rest.issues.createComment({
            owner: context.repo.owner,
            repo: context.repo.repo,
            issue_number: pr.number,
            body: pingBody,
        });
        core.info(`Posted readiness regression ping on #${pr.number}.`);
    }
};
