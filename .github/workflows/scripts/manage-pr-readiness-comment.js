'use strict';

const {
    MARKER_PREFIX,
    classifyCheckRuns,
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
    const headSha = context.payload.workflow_run.head_sha;
    const { data: associated } =
        await github.rest.repos.listPullRequestsAssociatedWithCommit({
            owner: context.repo.owner,
            repo: context.repo.repo,
            commit_sha: headSha,
        });

    return associated.find((pr) => pr.head.sha === headSha) || null;
}

module.exports = async ({ github, context, core }) => {
    const triggeringEvent = context.payload.workflow_run.event;
    if (triggeringEvent !== 'pull_request') {
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
