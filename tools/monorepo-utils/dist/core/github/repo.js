"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.isCommunityPullRequest = exports.getPullRequest = exports.createPullRequest = exports.deleteGithubBranch = exports.createGithubBranch = exports.getRefFromGithubBranch = exports.doesGithubBranchExist = exports.getLatestGithubReleaseVersion = void 0;
/**
 * Internal dependencies
 */
const api_1 = require("./api");
const getLatestGithubReleaseVersion = (options) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name } = options;
    const data = yield (0, api_1.graphqlWithAuth)()(`
			{
			    repository(owner: "${owner}", name: "${name}") {
					releases(
						first: 25
						orderBy: { field: CREATED_AT, direction: DESC }
					) {
						nodes {
							tagName
							isLatest
						}
					}
				}
			}
		`);
    return data.repository.releases.nodes.find((tagName) => tagName.isLatest).tagName;
});
exports.getLatestGithubReleaseVersion = getLatestGithubReleaseVersion;
const doesGithubBranchExist = (options, nextReleaseBranch) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name } = options;
    try {
        const branchOnGithub = yield (0, api_1.octokitWithAuth)().request('GET /repos/{owner}/{repo}/branches/{branch}', {
            owner,
            repo: name,
            branch: nextReleaseBranch,
        });
        return branchOnGithub.data.name === nextReleaseBranch;
    }
    catch (e) {
        if (e.status === 404 &&
            e.response.data.message === 'Branch not found') {
            return false;
        }
        throw new Error(e);
    }
});
exports.doesGithubBranchExist = doesGithubBranchExist;
const getRefFromGithubBranch = (options, source) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name } = options;
    const { repository } = yield (0, api_1.graphqlWithAuth)()(`
			{
			    repository(owner:"${owner}", name:"${name}") {
					ref(qualifiedName: "refs/heads/${source}") {
						target {
						  ... on Commit {
							  history(first: 1) {
								edges{ node{ oid } }
							  }
						  }
						}
					}
				  }
			}
		`);
    // @ts-expect-error: The graphql query is typed, but the response is not.
    return repository.ref.target.history.edges.shift().node.oid;
});
exports.getRefFromGithubBranch = getRefFromGithubBranch;
const createGithubBranch = (options, branch, ref) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name } = options;
    yield (0, api_1.octokitWithAuth)().request('POST /repos/{owner}/{repo}/git/refs', {
        owner,
        repo: name,
        ref: `refs/heads/${branch}`,
        sha: ref,
    });
});
exports.createGithubBranch = createGithubBranch;
const deleteGithubBranch = (options, branch) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name } = options;
    yield (0, api_1.octokitWithAuth)().request('DELETE /repos/{owner}/{repo}/git/refs/heads/{ref}', {
        owner,
        repo: name,
        ref: branch,
    });
});
exports.deleteGithubBranch = deleteGithubBranch;
/**
 * Create a pull request from branches on Github.
 *
 * @param {Object} options       pull request options.
 * @param {string} options.head  branch name containing the changes you want to merge.
 * @param {string} options.base  branch name you want the changes pulled into.
 * @param {string} options.owner repository owner.
 * @param {string} options.name  repository name.
 * @param {string} options.title pull request title.
 * @param {string} options.body  pull request body.
 * @return {Promise<object>}     pull request data.
 */
const createPullRequest = (options) => __awaiter(void 0, void 0, void 0, function* () {
    const { head, base, owner, name, title, body } = options;
    const pullRequest = yield (0, api_1.octokitWithAuth)().request('POST /repos/{owner}/{repo}/pulls', {
        owner,
        repo: name,
        title,
        body,
        head,
        base,
    });
    return pullRequest.data;
});
exports.createPullRequest = createPullRequest;
/**
 * Get a pull request from GitHub.
 *
 * @param {Object} options
 * @param {string} options.owner    repository owner.
 * @param {string} options.name     repository name.
 * @param {string} options.prNumber pull request number.
 * @return {Promise<object>}     pull request data.
 */
const getPullRequest = (options) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name, prNumber } = options;
    const pr = yield (0, api_1.octokitWithAuth)().request('GET /repos/{owner}/{repo}/pulls/{pull_number}', {
        owner,
        repo: name,
        pull_number: Number(prNumber),
    });
    return pr.data;
});
exports.getPullRequest = getPullRequest;
/**
 * Determine if a pull request is coming from a community contribution, i.e., not from a member of the WooCommerce organization.
 *
 * @param {Object} pullRequestData pull request data.
 * @param {string} owner           repository owner.
 * @param {string} name            repository name.
 * @return {boolean} if a pull request is coming from a community contribution.
 */
const isCommunityPullRequest = (pullRequestData, owner, name) => {
    // We can't use author_association here because it can be changed by PR authors. Instead check PR source.
    return pullRequestData.head.repo.full_name !== `${owner}/${name}`;
};
exports.isCommunityPullRequest = isCommunityPullRequest;
