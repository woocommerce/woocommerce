const assert = require('node:assert/strict');
const test = require('node:test');

const { selectPullRequestsToClose } = require('./close-linked-prs-on-issue-close');

const repository = 'woocommerce/woocommerce';

const issue = (number, state, nameWithOwner = repository) => ({
    number,
    state,
    repository: { nameWithOwner },
});

const pullRequest = (number, linkedIssues, overrides = {}) => ({
    number,
    state: 'OPEN',
    baseRefName: 'trunk',
    repository: { nameWithOwner: repository },
    closingIssuesReferences: { nodes: linkedIssues },
    ...overrides,
});

const select = (pullRequests) => selectPullRequestsToClose({ pullRequests, repository, defaultBranch: 'trunk' });

test('closes a pull request linked only to the closed issue', () => {
    assert.deepEqual(select([pullRequest(1, [issue(100, 'CLOSED')])]), { close: [1], keep: [] });
});

test('closes a pull request whose other linked issues are also closed', () => {
    const result = select([pullRequest(1, [issue(100, 'CLOSED'), issue(101, 'CLOSED')])]);

    assert.deepEqual(result.close, [1]);
});

test('keeps a pull request that is still linked to another open issue', () => {
    const result = select([pullRequest(1, [issue(100, 'CLOSED'), issue(101, 'OPEN')])]);

    assert.deepEqual(result.close, []);
    assert.equal(result.keep[0].number, 1);
});

test('keeps a pull request that targets a non-default branch', () => {
    const result = select([pullRequest(1, [issue(100, 'CLOSED')], { baseRefName: 'release/10.4' })]);

    assert.deepEqual(result.close, []);
});

test('keeps a pull request from another repository', () => {
    const result = select([
        pullRequest(1, [issue(100, 'CLOSED')], { repository: { nameWithOwner: 'woocommerce/other' } }),
    ]);

    assert.deepEqual(result.close, []);
});

test('ignores merged and closed pull requests', () => {
    const result = select([
        pullRequest(1, [issue(100, 'CLOSED')], { state: 'MERGED' }),
        pullRequest(2, [issue(100, 'CLOSED')], { state: 'CLOSED' }),
    ]);

    assert.deepEqual(result, { close: [], keep: [] });
});
