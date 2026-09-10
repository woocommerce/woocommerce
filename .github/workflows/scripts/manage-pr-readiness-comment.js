'use strict';

const {
    MARKER_PREFIX,
    CI_WORKFLOW_FILE,
    classifyCheckRuns,
    decideAction,
    buildCommentBody,
} = require('./pr-readiness-checklist');

async function findExistingComment(github, context, prNumber) {
    const comments = await github.paginate(github.rest.issues.listComments, {
        owner: context.repo.owner,
        repo: context.repo.repo,
        issue_number: prNumber,
        per_page: 100,
    });

    // Optional chaining: a deleted account leaves `user` null (ghost), and
    // `body` can likewise be absent - either one on any unrelated comment
    // in the thread would otherwise throw and fail the whole run.
    return (
        comments.find(
            (comment) =>
                comment.user?.login === 'github-actions[bot]' &&
                comment.body?.includes(MARKER_PREFIX)
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

// Human-readable log lines for decideAction's skip reasons. Purely
// cosmetic: the decision itself is made (and tested) in the pure module.
const SKIP_LOG_MESSAGES = {
    draft: () => 'Pull request is a draft, skipping.',
    'not-a-fork': () => 'Pull request is not from a fork, skipping.',
    'no-applicable-checks': (pr) =>
        `No applicable checks for #${pr.number} on ${pr.head.sha}; leaving any existing comment as-is.`,
    'pending-checks': (pr) =>
        `Some checks for #${pr.number} are still in progress; deferring instead of reporting a premature all-clear.`,
    'ci-not-run': (pr, ciRun) =>
        `CI has not produced results for #${pr.number} yet (${
            ciRun
                ? `status=${ciRun.status}, conclusion=${ciRun.conclusion}`
                : 'no CI run for this SHA'
        }); deferring instead of reporting an all-clear on checks that never ran.`,
    'still-clear': (pr) =>
        `Readiness state unchanged for #${pr.number}; skipping comment update.`,
};

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

    let checkRuns;
    try {
        // No map function: octokit's paginate plugin normalizes this
        // endpoint's namespaced { total_count, check_runs } payload into a
        // bare check-run array before any map function runs. A mapper
        // returning response.data.check_runs therefore returns undefined,
        // and paginate concat()s that into [undefined] - found live in
        // round-3 verification (PR #67912), where it crashed the
        // classifier on every run.
        checkRuns = await github.paginate(github.rest.checks.listForRef, {
            owner: context.repo.owner,
            repo: context.repo.repo,
            ref: pr.head.sha,
            per_page: 100,
        });
    } catch (error) {
        core.warning(`Failed to fetch check-runs for ${pr.head.sha}: ${error.message}`);
        return;
    }

    const { tasks, hasPending } = classifyCheckRuns(checkRuns);

    // A failed lookup leaves ciRun undefined rather than aborting: the
    // decision then defers the all-clear path (no CI evidence) but still
    // reports failures already found.
    let ciRun;
    try {
        ciRun = await findCiRun(github, context, pr.head.sha);
    } catch (error) {
        core.warning(
            `Failed to look up CI runs for ${pr.head.sha}: ${error.message}`
        );
    }

    // Every reason this event might be a no-op lives in decideAction, where
    // it's unit-tested. It runs twice so the sticky comment is fetched
    // lazily: the first pass decides without it, and every skip reason that
    // doesn't depend on the existing comment (draft, fork, empty checklist,
    // pending checks, no CI evidence) resolves right there - sparing the
    // paginated listComments walk on the bot's most common path, the
    // mid-CI deferral. Only when the first pass would act is the comment
    // fetched and the decision re-taken with it, which can still land on
    // the one comment-dependent skip (clear->clear).
    let existingComment = null;
    let decision = decideAction({ pr, tasks, hasPending, ciRun, existingComment });
    if (decision.action !== 'skip') {
        existingComment = await findExistingComment(github, context, pr.number);
        decision = decideAction({ pr, tasks, hasPending, ciRun, existingComment });
    }

    if (decision.action === 'skip') {
        core.info(SKIP_LOG_MESSAGES[decision.reason](pr, ciRun));
        return;
    }

    const { body, pingBody, mentioned } = buildCommentBody({
        tasks,
        previousState: decision.previousState,
        authorLogin: pr.user.login,
        // Only relevant when a ping fires, which only happens when a
        // sticky comment already exists (clear->failing requires a prior
        // 'clear' state) - html_url comes straight from the listComments
        // fetch, no extra API call, and (like job html_urls) is generated
        // deterministically by GitHub, never derived from PR content.
        stickyCommentUrl: existingComment ? existingComment.html_url : null,
    });

    if (decision.action === 'update') {
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

    // Whether the author was actually notified is the question every
    // "did the contributor see this?" debugging session starts with, so
    // put the answer in the log. Only a *created* comment notifies - the
    // sticky comment's own creation (which carries a mention exactly on
    // the transitions that set `mentioned`) or the separate ping - while
    // an edit never does, whatever its intro line says.
    const notified = (mentioned && decision.action === 'create') || Boolean(pingBody);
    core.info(
        notified
            ? `Author @${pr.user.login} was notified.`
            : 'No notification sent for this update (comment edits never notify).'
    );
};
