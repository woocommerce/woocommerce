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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.versionBumpCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const simple_git_1 = __importDefault(require("simple-git"));
/**
 * Internal dependencies
 */
const logger_1 = require("../../../core/logger");
const git_1 = require("../../../core/git");
const repo_1 = require("../../../core/github/repo");
const environment_1 = require("../../../core/environment");
const version_1 = require("../../../core/version");
const bump_1 = require("./bump");
const validate_1 = require("./lib/validate");
exports.versionBumpCommand = new extra_typings_1.Command('version-bump')
    .description('Bump versions ahead of new development cycle')
    .argument('<version>', 'Version to bump to')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-b --base <base>', 'Base branch to create the PR against. Default: trunk', 'trunk')
    .option('-d --dry-run', 'Prepare the version bump and log a diff. Do not create a PR or push to branch', false)
    .option('-c --commit-direct-to-base', 'Commit directly to the base branch. Do not create a PR just push directly to base branch', false)
    .option('-f --force', 'Force a version bump, even when the new version is less than the existing version', false)
    .option('-a --allow-accel', 'Allow accelerated versioning. When this option is not present, versions must be semantically correct', false)
    .action((version, options) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name, base, dryRun, commitDirectToBase } = options;
    logger_1.Logger.startTask(`Making a temporary clone of '${owner}/${name}'`);
    const source = `github.com/${owner}/${name}`;
    const token = (0, environment_1.getEnvVar)('GITHUB_TOKEN', true);
    const remote = `https://${owner}:${token}@${source}`;
    const tmpRepoPath = yield (0, git_1.sparseCheckoutRepoShallow)(remote, 'woocommerce', [
        'plugins/woocommerce/includes/class-woocommerce.php',
        // All that's needed is the line above, but including these here for completeness.
        'plugins/woocommerce/composer.json',
        'plugins/woocommerce/package.json',
        'plugins/woocommerce/readme.txt',
        'plugins/woocommerce/woocommerce.php',
    ]);
    logger_1.Logger.endTask();
    logger_1.Logger.notice(`Temporary clone of '${owner}/${name}' created at ${tmpRepoPath}`);
    const git = (0, simple_git_1.default)({
        baseDir: tmpRepoPath,
        config: ['core.hooksPath=/dev/null'],
    });
    const majorMinor = (0, validate_1.getIsAccelRelease)(version)
        ? version
        : (0, version_1.getMajorMinor)(version);
    const branch = `prep/${base}-for-next-dev-cycle-${majorMinor}`;
    try {
        if (commitDirectToBase) {
            if (base === 'trunk') {
                logger_1.Logger.error(`The --commit-direct-to-base option cannot be used with the trunk branch as a base. A pull request must be created instead.`);
            }
            logger_1.Logger.notice(`Checking out ${base}`);
            yield (0, git_1.checkoutRemoteBranch)(tmpRepoPath, base);
        }
        else {
            const exists = yield git.raw('ls-remote', 'origin', branch);
            if (!dryRun && exists.trim().length > 0) {
                logger_1.Logger.error(`Branch ${branch} already exists. Run \`git push <remote> --delete ${branch}\` and rerun this command.`);
            }
            if (base !== 'trunk') {
                // if the base is not trunk, we need to checkout the base branch first before creating a new branch.
                logger_1.Logger.notice(`Checking out ${base}`);
                yield (0, git_1.checkoutRemoteBranch)(tmpRepoPath, base);
            }
            logger_1.Logger.notice(`Creating new branch ${branch}`);
            yield git.checkoutBranch(branch, base);
        }
        logger_1.Logger.notice('Validating arguments');
        yield (0, validate_1.validateArgs)(tmpRepoPath, version, options);
        const workingBranch = commitDirectToBase ? base : branch;
        logger_1.Logger.notice(`Bumping versions in ${owner}/${name} on ${workingBranch} branch`);
        yield (0, bump_1.bumpFiles)(tmpRepoPath, version);
        if (dryRun) {
            const diff = yield git.diffSummary();
            logger_1.Logger.notice(`The version has been bumped to ${version} in the following files:`);
            logger_1.Logger.warn(diff.files.map((f) => f.file).join('\n'));
            logger_1.Logger.notice('Dry run complete. No pull was request created nor was a commit made.');
            return;
        }
        logger_1.Logger.notice('Adding and committing changes');
        yield git.add('.');
        yield git.commit(`Prep ${base} for ${majorMinor} cycle with version bump to ${version}`);
        logger_1.Logger.notice(`Pushing ${workingBranch} branch to Github`);
        yield git.push('origin', workingBranch);
        if (!commitDirectToBase) {
            logger_1.Logger.startTask('Creating a pull request');
            const pullRequest = yield (0, repo_1.createPullRequest)({
                owner,
                name,
                title: `Prep ${base} for ${majorMinor} cycle`,
                body: `This PR updates the versions in ${base} to ${version}.`,
                head: branch,
                base,
            });
            logger_1.Logger.notice(`Pull request created: ${pullRequest.html_url}`);
            logger_1.Logger.endTask();
        }
    }
    catch (error) {
        logger_1.Logger.error(error);
    }
}));
