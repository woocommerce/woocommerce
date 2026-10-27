const fs = require('fs');

const LINKED_PULL_REQUESTS_QUERY = `
    query ($owner: String!, $repo: String!, $number: Int!) {
        repository(owner: $owner, name: $repo) {
            defaultBranchRef {
                name
            }
            issue(number: $number) {
                state
                closedByPullRequestsReferences(first: 50, includeClosedPrs: false) {
                    nodes {
                        number
                        state
                        baseRefName
                        repository {
                            nameWithOwner
                        }
                        closingIssuesReferences(first: 50) {
                            nodes {
                                number
                                state
                                repository {
                                    nameWithOwner
                                }
                            }
                        }
                    }
                }
            }
        }
    }
`;

/**
 * Splits the pull requests linked to a closed issue into the ones to close and the ones to keep open.
 * A pull request stays open when it lives in another repository, targets a branch other than the
 * default one (e.g. a release branch cherry-pick), or still resolves another open issue.
 *
 * @param {Object}   params
 * @param {Object[]} params.pullRequests  Linked pull requests from the GraphQL query.
 * @param {string}   params.repository    The closed issue's repository, as "owner/repo".
 * @param {string}   params.defaultBranch The repository's default branch.
 * @return {{close: number[], keep: {number: number, reason: string}[]}}
 */
function selectPullRequestsToClose({ pullRequests, repository, defaultBranch }) {
    const close = [];
    const keep = [];

    for (const pr of pullRequests) {
        // `includeClosedPrs: false` still returns merged PRs, such as the one that closed the issue.
        if (pr.state !== 'OPEN') {
            continue;
        }

        if (pr.repository.nameWithOwner !== repository) {
            keep.push({ number: pr.number, reason: `it belongs to ${pr.repository.nameWithOwner}` });
            continue;
        }

        if (pr.baseRefName !== defaultBranch) {
            keep.push({ number: pr.number, reason: `it targets ${pr.baseRefName}, not ${defaultBranch}` });
            continue;
        }

        const otherOpenIssues = pr.closingIssuesReferences.nodes.filter((issue) => issue.state === 'OPEN');
        if (otherOpenIssues.length > 0) {
            const refs = otherOpenIssues.map((issue) => `${issue.repository.nameWithOwner}#${issue.number}`);
            keep.push({ number: pr.number, reason: `it is still linked to open issue(s) ${refs.join(', ')}` });
            continue;
        }

        close.push(pr.number);
    }

    return { close, keep };
}

/**
 * Closes open pull requests that were linked to an issue that just got closed, leaving a comment on each.
 * When REPORT_PATH is set, writes a JSON report of the closed, kept, and failed pull requests there.
 *
 * @param {Object} params - The parameters object
 * @param {Object} params.github - GitHub API client
 * @param {Object} params.context - GitHub Actions context
 * @param {Object} params.core - GitHub Actions core utilities
 */
module.exports = async ({ github, context, core }) => {
    const { owner, repo } = context.repo;
    const issueNumber = context.payload.issue.number;

    const { repository: result } = await github.graphql(LINKED_PULL_REQUESTS_QUERY, {
        owner,
        repo,
        number: issueNumber,
    });

    // The issue may have been reopened while this job was queued.
    if (result.issue.state === 'OPEN') {
        core.info(`Issue #${issueNumber} has been reopened. Leaving its linked pull requests open.`);
        return;
    }

    const { close, keep } = selectPullRequestsToClose({
        pullRequests: result.issue.closedByPullRequestsReferences.nodes,
        repository: `${owner}/${repo}`,
        defaultBranch: result.defaultBranchRef.name,
    });

    if (close.length === 0 && keep.length === 0) {
        core.info(`No open pull requests are linked to issue #${issueNumber}.`);
        return;
    }

    for (const { number, reason } of keep) {
        core.info(`Leaving PR #${number} open because ${reason}.`);
    }

    const closed = [];
    const failed = [];
    // Close before commenting, so a failed close never leaves a comment saying the PR was closed.
    for (const prNumber of close) {
        try {
            await github.rest.pulls.update({
                owner,
                repo,
                pull_number: prNumber,
                state: 'closed',
            });
        } catch (error) {
            core.warning(`Failed to close PR #${prNumber}: ${error.message}`);
            failed.push({ number: prNumber, error: error.message });
            continue;
        }
        closed.push(prNumber);
        core.info(`Closed PR #${prNumber}.`);

        try {
            await github.rest.issues.createComment({
                owner,
                repo,
                issue_number: prNumber,
                body: [
                    `Closing this pull request because the issue it resolves, #${issueNumber}, was closed and it isn't linked to any other open issue.`,
                    '',
                    'If this pull request is still needed, reopen it and link it to an open issue.',
                ].join('\n'),
            });
        } catch (error) {
            core.warning(`Closed PR #${prNumber} but failed to comment on it: ${error.message}`);
            failed.push({ number: prNumber, error: `Closed, but the comment failed: ${error.message}` });
        }
    }

    if (process.env.REPORT_PATH) {
        const report = { issue: issueNumber, closed, kept: keep, failed };
        fs.writeFileSync(process.env.REPORT_PATH, `${JSON.stringify(report, null, 2)}\n`);
    }

    if (failed.length > 0) {
        core.setFailed(`Failed to close or comment on PR(s): ${failed.map(({ number }) => `#${number}`).join(', ')}`);
    }
};

module.exports.selectPullRequestsToClose = selectPullRequestsToClose;
