'use strict';

const {
    MARKER_PREFIX,
    CI_WORKFLOW_FILE,
    ciHasProducedResults,
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

// Look up the CI workflow run for this SHA, so the all-clear path can be
// gated on evidence that CI actually ran rather than on check-run absence.
// Returns undefined when no CI run exists yet, which is itself the answer.
async function findCiRun(github, context, headSha) {
    // One targeted request instead of paginating every workflow's runs for
    // the SHA. `event: 'pull_request'` excludes a push-event CI run that
    // could exist for the same commit once the branch lands somewhere.
    const { data } = await github.rest.actions.listWorkflowRuns({
        owner: context.repo.owner,
        repo: context.repo.repo,
        workflow_id: CI_WORKFLOW_FILE,
        head_sha: headSha,
        event: 'pull_request',
        per_page: 100,
    });

    // Newest first, so a re-run supersedes the run it replaced.
    return data.workflow_runs[0];
}

async function resolvePullRequest(github, context, core) {
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

    // The fork is the contributor's repo, and contributors delete, rename,
    // or privatize forks while their PR is still open - all of which 404
    // this call. Degrade to the (possibly lagging) base-repo query instead
    // of failing the job.
    let associated;
    try {
        ({ data: associated } =
            await github.rest.repos.listPullRequestsAssociatedWithCommit({
                owner: sourceOwner,
                repo: sourceRepo,
                commit_sha: headSha,
                per_page: 100,
            }));
    } catch (error) {
        core.warning(
            `Failed to resolve PR from ${sourceOwner}/${sourceRepo}: ${error.message}; falling back to the base repo.`
        );
        ({ data: associated } =
            await github.rest.repos.listPullRequestsAssociatedWithCommit({
                owner: context.repo.owner,
                repo: context.repo.repo,
                commit_sha: headSha,
                per_page: 100,
            }));
    }

    // Matching on head.sha also doubles as the stale-event guard: a
    // workflow_run event for a superseded commit matches no PR whose head
    // has since moved on, so it resolves to null and exits cleanly.
    //
    // The base-repo and open-state constraints are what make the resolved
    // number safe to comment on. `associated` lists PRs from the fork's
    // perspective, which can include the contributor's own fork-internal PR
    // for the same commit (or one targeting another repo entirely) - and a
    // number from a foreign repo, passed to createComment against
    // context.repo, would land on an unrelated issue. Likewise, without the
    // open check, a post-merge re-run of CI on this SHA would resolve to the
    // merged PR and revive its comment.
    const baseFullName = `${context.repo.owner}/${context.repo.repo}`;

    return (
        associated.find(
            (pr) =>
                pr.head.sha === headSha &&
                pr.state === 'open' &&
                pr.base.repo.full_name === baseFullName
        ) || null
    );
}

module.exports = async ({ github, context, core }) => {
    const triggeringEvent = context.payload.workflow_run.event;
    if (!['pull_request', 'pull_request_target'].includes(triggeringEvent)) {
        core.info(`Ignoring workflow_run for event '${triggeringEvent}'.`);
        return;
    }

    const pr = await resolvePullRequest(github, context, core);
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

    const { tasks, hasPending } = classifyCheckRuns(checkRuns);

    const existingComment = await findExistingComment(github, context, pr.number);
    const previousState = parsePreviousState(
        existingComment && existingComment.body
    );

    // An empty checklist is not an all-clear, it is an empty one. A push
    // where every tracked check is skipped (a docs- or .github-only commit)
    // classifies to zero tasks, which computeOverallState calls 'clear' -
    // and against an existing 'failing' comment that would announce "all
    // checks are passing now" on a commit where nothing ran. Leave whatever
    // state the previous push established alone.
    if (tasks.length === 0) {
        core.info(
            `No applicable checks for #${pr.number} on ${pr.head.sha}; leaving any existing comment as-is.`
        );
        return;
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
        core.info(
            `Some checks for #${pr.number} are still in progress; deferring instead of reporting a premature all-clear.`
        );
        return;
    }

    // A clear checklist built entirely from checks that never ran is not an
    // all-clear, it is an empty one - and the two are indistinguishable from
    // check-run data alone (see ciHasProducedResults). Confirm CI actually
    // produced results for this SHA before saying everything passed. Only the
    // clear path is gated: a failure already found is actionable now and is
    // still reported immediately, even while sibling jobs are running.
    if (overallState === 'clear') {
        let ciRun;
        try {
            ciRun = await findCiRun(github, context, pr.head.sha);
        } catch (error) {
            core.warning(
                `Failed to look up CI runs for ${pr.head.sha}: ${error.message}`
            );
            return;
        }

        if (!ciHasProducedResults(ciRun)) {
            core.info(
                `CI has not produced results for #${pr.number} yet (${
                    ciRun
                        ? `status=${ciRun.status}, conclusion=${ciRun.conclusion}`
                        : 'no CI run for this SHA'
                }); deferring instead of reporting an all-clear on checks that never ran.`
            );
            return;
        }
    }

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
        // Only relevant when a ping fires, which only happens when a
        // sticky comment already exists (clear->failing requires a prior
        // 'clear' state) - html_url comes straight from the listComments
        // fetch, no extra API call, and (like job html_urls) is generated
        // deterministically by GitHub, never derived from PR content.
        stickyCommentUrl: existingComment ? existingComment.html_url : null,
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
