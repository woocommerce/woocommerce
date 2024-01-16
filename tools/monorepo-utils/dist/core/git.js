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
exports.checkoutRemoteBranch = exports.generateDiff = exports.getPullRequestNumberFromHash = exports.getLineCommitHash = exports.getCommitHash = exports.diffHashes = exports.checkoutRef = exports.sparseCheckoutRepoShallow = exports.sparseCheckoutRepo = exports.cloneAuthenticatedRepo = exports.getAuthenticatedRemote = exports.cloneRepoShallow = exports.cloneRepo = exports.getPatches = exports.getStartingLineNumber = exports.getFilename = void 0;
/**
 * External dependencies
 */
const child_process_1 = require("child_process");
const path_1 = require("path");
const os_1 = require("os");
const fs_1 = require("fs");
const simple_git_1 = require("simple-git");
const uuid_1 = require("uuid");
const promises_1 = require("fs/promises");
const node_url_1 = require("node:url");
/**
 * Internal dependencies
 */
const environment_1 = require("./environment");
/**
 * Get filename from patch
 *
 * @param {string} str String to extract filename from.
 * @return {string} formatted filename.
 */
const getFilename = (str) => {
    return str.replace(/^a(.*)\s.*/, '$1');
};
exports.getFilename = getFilename;
/**
 * Get starting line number from patch
 *
 * @param {string} str String to extract starting line number from.
 * @return {number} line number.
 */
const getStartingLineNumber = (str) => {
    const lineNumber = str.replace(/^@@ -\d+,\d+ \+(\d+),\d+ @@.*?$/, '$1');
    if (!lineNumber.match(/^\d+$/)) {
        throw new Error('Unable to parse line number from patch');
    }
    return parseInt(lineNumber, 10);
};
exports.getStartingLineNumber = getStartingLineNumber;
/**
 * Get patches
 *
 * @param {string} content Patch content.
 * @param {RegExp} regex   Regex to find specific patches.
 * @return {string[]} Array of patches.
 */
const getPatches = (content, regex) => {
    const patches = content.split('diff --git ');
    const changes = [];
    for (const p in patches) {
        const patch = patches[p];
        const id = patch.match(regex);
        if (id) {
            changes.push(patch);
        }
    }
    return changes;
};
exports.getPatches = getPatches;
/**
 * Check if a string is a valid url.
 *
 * @param {string} maybeURL - the URL string to check
 * @return {boolean} whether the string is a valid URL or not.
 */
const isUrl = (maybeURL) => {
    try {
        new node_url_1.URL(maybeURL);
        return true;
    }
    catch (e) {
        return false;
    }
};
/**
 * Clone a git repository.
 *
 * @param {string}      repoPath - the path (either URL or file path) to the repo to clone.
 * @param {TaskOptions} options  - options to pass to simple-git.
 * @return {Promise<string>} the path to the cloned repo.
 */
const cloneRepo = (repoPath, options = {}) => __awaiter(void 0, void 0, void 0, function* () {
    const folderPath = (0, path_1.join)((0, os_1.tmpdir)(), 'code-analyzer-tmp', (0, uuid_1.v4)());
    (0, fs_1.mkdirSync)(folderPath, { recursive: true });
    const git = (0, simple_git_1.simpleGit)({ baseDir: folderPath });
    yield git.clone(repoPath, folderPath, options);
    // If this is a local clone then the simplest way to maintain remote settings is to copy git config across
    if (!isUrl(repoPath)) {
        (0, child_process_1.execSync)(`cp ${repoPath}/.git/config ${folderPath}/.git/config`);
    }
    // Update the repo.
    yield git.fetch();
    return folderPath;
});
exports.cloneRepo = cloneRepo;
/**
 * Clone a git repository without history.
 *
 * @param {string} repoPath - the path (either URL or file path) to the repo to clone.
 * @return {Promise<string>} the path to the cloned repo.
 */
const cloneRepoShallow = (repoPath) => __awaiter(void 0, void 0, void 0, function* () {
    return yield (0, exports.cloneRepo)(repoPath, { '--depth': 1 });
});
exports.cloneRepoShallow = cloneRepoShallow;
/**
 * Add a remote using the authenticated token `GITHUB_TOKEN`
 *
 * @param {Object} options       CLI options
 * @param {string} options.owner repo owner
 * @param {string} options.name  repo name
 * @return {string} remote
 */
const getAuthenticatedRemote = (options) => {
    const { owner, name } = options;
    const source = `github.com/${owner}/${name}`;
    const token = (0, environment_1.getEnvVar)('GITHUB_TOKEN', true);
    return `https://${owner}:${token}@${source}`;
};
exports.getAuthenticatedRemote = getAuthenticatedRemote;
/**
 * Clone a repo using the authenticated token `GITHUB_TOKEN`. This allows the script to push branches to origin.
 *
 * @param {Object}  options       CLI options
 * @param {string}  options.owner repo owner
 * @param {string}  options.name  repo name
 * @param {boolean} isShallow     whether to do a shallow clone or not.
 * @return {string} temporary repo path
 */
const cloneAuthenticatedRepo = (options, isShallow = true) => __awaiter(void 0, void 0, void 0, function* () {
    const remote = (0, exports.getAuthenticatedRemote)(options);
    return isShallow
        ? yield (0, exports.cloneRepoShallow)(remote)
        : yield (0, exports.cloneRepo)(remote);
});
exports.cloneAuthenticatedRepo = cloneAuthenticatedRepo;
/**
 * Do a minimal sparse checkout of a github repo.
 *
 * @param {string}        githubRepoUrl - the URL to the repo to checkout.
 * @param {string}        path          - the path to checkout to.
 * @param {Array<string>} directories   - the files or directories to checkout.
 * @param {string}        base          - the base branch to checkout from. Defaults to trunk.
 * @param {TaskOptions}   options       - options to pass to simple-git.
 * @return {Promise<string>}  the path to the cloned repo.
 */
const sparseCheckoutRepo = (githubRepoUrl, path, directories, base = 'trunk', options = {}) => __awaiter(void 0, void 0, void 0, function* () {
    const folderPath = (0, path_1.join)((0, os_1.tmpdir)(), path);
    // clean up if it already exists.
    yield (0, promises_1.rm)(folderPath, { recursive: true, force: true });
    yield (0, promises_1.mkdir)(folderPath, { recursive: true });
    const git = (0, simple_git_1.simpleGit)({ baseDir: folderPath });
    const cloneOptions = { '--no-checkout': null };
    yield git.clone(githubRepoUrl, folderPath, Object.assign(Object.assign({}, cloneOptions), options));
    yield git.raw('sparse-checkout', 'init', { '--cone': null });
    yield git.raw('sparse-checkout', 'set', directories.join(' '));
    yield git.checkout(base);
    return folderPath;
});
exports.sparseCheckoutRepo = sparseCheckoutRepo;
/**
 * Do a minimal sparse checkout of a github repo without history.
 *
 * @param {string}        githubRepoUrl - the URL to the repo to checkout.
 * @param {string}        path          - the path to checkout to.
 * @param {Array<string>} directories   - the files or directories to checkout.
 * @return {Promise<string>}  the path to the cloned repo.
 */
const sparseCheckoutRepoShallow = (githubRepoUrl, path, directories, base = 'trunk') => __awaiter(void 0, void 0, void 0, function* () {
    return yield (0, exports.sparseCheckoutRepo)(githubRepoUrl, path, directories, base, {
        '--depth': 1,
    });
});
exports.sparseCheckoutRepoShallow = sparseCheckoutRepoShallow;
/**
 * checkoutRef - checkout a ref in a git repo.
 *
 * @param {string} pathToRepo - the path to the repo to checkout a ref from.
 * @param {string} ref        - the ref to checkout.
 * @return {Response<string>} - the simple-git response.
 */
const checkoutRef = (pathToRepo, ref) => {
    const git = (0, simple_git_1.simpleGit)({
        baseDir: pathToRepo,
        config: ['core.hooksPath=/dev/null'],
    });
    return git.checkout(ref);
};
exports.checkoutRef = checkoutRef;
/**
 * Do a git diff of 2 commit hashes (or branches)
 *
 * @param {string}        baseDir      - baseDir that the repo is in
 * @param {string}        hashA        - either a git commit hash or a git branch
 * @param {string}        hashB        - either a git commit hash or a git branch
 * @param {Array<string>} excludePaths - A list of paths to exclude from the diff
 * @return {Promise<string>} - diff of the changes between the 2 hashes
 */
const diffHashes = (baseDir, hashA, hashB, excludePaths = []) => {
    const git = (0, simple_git_1.simpleGit)({ baseDir });
    if (excludePaths.length) {
        return git.diff([
            `${hashA}..${hashB}`,
            '--',
            '.',
            ...excludePaths.map((ps) => `:^${ps}`),
        ]);
    }
    return git.diff([`${hashA}..${hashB}`]);
};
exports.diffHashes = diffHashes;
/**
 * Determines if a string is a commit hash or not.
 *
 * @param {string} ref - the ref to check
 * @return {boolean} whether the ref is a commit hash or not.
 */
const refIsHash = (ref) => {
    return /^[0-9a-f]{7,40}$/i.test(ref);
};
/**
 * Get the commit hash for a ref (either branch or commit hash). If a validly
 * formed hash is provided it is returned unmodified.
 *
 * @param {string} baseDir - the dir of the git repo to get the hash from.
 * @param {string} ref     - Either a commit hash or a branch name.
 * @return {string} - the commit hash of the ref.
 */
const getCommitHash = (baseDir, ref) => __awaiter(void 0, void 0, void 0, function* () {
    const isHash = refIsHash(ref);
    // check if its in history, if its not an error will be thrown
    try {
        yield (0, simple_git_1.simpleGit)({ baseDir }).show(ref);
    }
    catch (e) {
        throw new Error(`${ref} is not a valid commit hash or branch name that exists in git history`);
    }
    // If its not a hash we assume its a branch
    if (!isHash) {
        return (0, simple_git_1.simpleGit)({ baseDir }).revparse([ref]);
    }
    // Its a hash already
    return ref;
});
exports.getCommitHash = getCommitHash;
/**
 * Get the commit hash for the last change to a line within a specific file.
 *
 * @param {string} baseDir    - the dir of the git repo to get the hash from.
 * @param {string} filePath   - the relative path to the file to check the commit hash of.
 * @param {number} lineNumber - the line number from which to get the hash of the last commit.
 * @return {string} - the commit hash of the last change to filePath at lineNumber.
 */
const getLineCommitHash = (baseDir, filePath, lineNumber) => __awaiter(void 0, void 0, void 0, function* () {
    // Remove leading slash, if it exists.
    const adjustedFilePath = filePath.replace(/^\//, '');
    try {
        const git = yield (0, simple_git_1.simpleGit)({ baseDir });
        const blame = yield git.raw([
            'blame',
            `-L${lineNumber},${lineNumber}`,
            adjustedFilePath,
        ]);
        const hash = blame.match(/^([a-f0-9]+)\s+/);
        if (!hash) {
            throw new Error(`Unable to git blame ${adjustedFilePath}:${lineNumber}`);
        }
        return hash[1];
    }
    catch (e) {
        throw new Error(`Unable to git blame ${adjustedFilePath}:${lineNumber}`);
    }
});
exports.getLineCommitHash = getLineCommitHash;
/**
 * Get the commit hash for the last change to a line within a specific file.
 *
 * @param {string} baseDir - the dir of the git repo to get the PR number from.
 * @param {string} hash    - the hash to get the PR number from.
 * @return {number} - the pull request number from the given inputs.
 */
const getPullRequestNumberFromHash = (baseDir, hash) => __awaiter(void 0, void 0, void 0, function* () {
    try {
        const git = yield (0, simple_git_1.simpleGit)({
            baseDir,
            config: ['core.hooksPath=/dev/null'],
        });
        const formerHead = yield git.revparse('HEAD');
        yield git.checkout(hash);
        const cmdOutput = yield git.raw([
            'log',
            '-1',
            '--first-parent',
            '--format=%cI\n%s',
        ]);
        const cmdLines = cmdOutput.split('\n');
        yield git.checkout(formerHead);
        const prNumber = cmdLines[1]
            .trim()
            .match(/(?:^Merge pull request #(\d+))|(?:\(#(\d+)\)$)/);
        if (prNumber) {
            return prNumber[1]
                ? parseInt(prNumber[1], 10)
                : parseInt(prNumber[2], 10);
        }
        throw new Error(`Unable to get PR number from hash ${hash}.`);
    }
    catch (e) {
        throw new Error(`Unable to get PR number from hash ${hash}.`);
    }
});
exports.getPullRequestNumberFromHash = getPullRequestNumberFromHash;
/**
 * generateDiff generates a diff for a given repo and 2 hashes or branch names.
 *
 * @param {string}        tmpRepoPath  - filepath to the repo to generate a diff from.
 * @param {string}        hashA        - commit hash or branch name.
 * @param {string}        hashB        - commit hash or branch name.
 * @param {Function}      onError      - the handler to call when an error occurs.
 * @param {Array<string>} excludePaths - A list of directories to exclude from the diff.
 */
const generateDiff = (tmpRepoPath, hashA, hashB, onError, excludePaths = []) => __awaiter(void 0, void 0, void 0, function* () {
    try {
        const git = (0, simple_git_1.simpleGit)({
            baseDir: tmpRepoPath,
            config: ['core.hooksPath=/dev/null'],
        });
        const validBranches = [hashA, hashB].filter((hash) => !refIsHash(hash));
        // checking out any branches will automatically track remote branches.
        for (const validBranch of validBranches) {
            // Note you can't do checkouts in parallel otherwise the git binary will crash
            yield git.checkout([validBranch]);
        }
        // turn both hashes into commit hashes if they are not already.
        const commitHashA = yield (0, exports.getCommitHash)(tmpRepoPath, hashA);
        const commitHashB = yield (0, exports.getCommitHash)(tmpRepoPath, hashB);
        const isRepo = yield (0, simple_git_1.simpleGit)({
            baseDir: tmpRepoPath,
        }).checkIsRepo();
        if (!isRepo) {
            throw new Error('Not a git repository');
        }
        const diff = yield (0, exports.diffHashes)(tmpRepoPath, commitHashA, commitHashB, excludePaths);
        return diff;
    }
    catch (e) {
        if (e instanceof Error) {
            onError(`Unable to create diff. Check that git repo, base hash, and compare hash all exist.\n Error: ${e.message}`);
        }
        else {
            onError('Unable to create diff. Check that git repo, base hash, and compare hash all exist.');
        }
        return '';
    }
});
exports.generateDiff = generateDiff;
/**
 *
 * @param {string}  tmpRepoPath path to temporary repo
 * @param {string}  branch      remote branch to checkout
 * @param {boolean} isShallow   whether to do a shallow clone and get only the latest commit
 */
const checkoutRemoteBranch = (tmpRepoPath, branch, isShallow = true) => __awaiter(void 0, void 0, void 0, function* () {
    const git = (0, simple_git_1.simpleGit)({
        baseDir: tmpRepoPath,
        config: ['core.hooksPath=/dev/null'],
    });
    // When the clone is shallow, we need to call this before fetching.
    yield git.raw(['remote', 'set-branches', '--add', 'origin', branch]);
    const fetchArgs = ['fetch', 'origin', branch];
    if (isShallow) {
        fetchArgs.push('--depth=1');
    }
    yield git.raw(fetchArgs);
    yield git.raw(['checkout', '-b', branch, `origin/${branch}`]);
});
exports.checkoutRemoteBranch = checkoutRemoteBranch;
