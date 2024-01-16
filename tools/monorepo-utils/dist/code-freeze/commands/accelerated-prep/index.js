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
exports.acceleratedPrepCommand = void 0;
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
const prep_1 = require("./lib/prep");
exports.acceleratedPrepCommand = new extra_typings_1.Command('accelerated-prep')
    .description('Prep for an accelerated release')
    .argument('<version>', 'Version to bump to use for changelog')
    .argument('<date>', 'Release date to use in changelog')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-b --base <base>', 'Base branch to create the PR against. Default: trunk', 'trunk')
    .option('-d --dry-run', 'Prepare the version bump and log a diff. Do not create a PR or push to branch', false)
    .option('-c --commit-direct-to-base', 'Commit directly to the base branch. Do not create a PR just push directly to base branch', false)
    .action((version, date, options) => __awaiter(void 0, void 0, void 0, function* () {
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
    const branch = `prep/${base}-accelerated`;
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
        const workingBranch = commitDirectToBase ? base : branch;
        logger_1.Logger.notice(`Adding Woo header to main plugin file and creating changelog.txt on ${workingBranch} branch`);
        (0, prep_1.addHeader)(tmpRepoPath);
        (0, prep_1.createChangelog)(tmpRepoPath, version, date);
        if (dryRun) {
            const diff = yield git.diffSummary();
            logger_1.Logger.notice(`The prep has been completed in the following files:`);
            logger_1.Logger.warn(diff.files.map((f) => f.file).join('\n'));
            logger_1.Logger.notice('Dry run complete. No pull was request created nor was a commit made.');
            return;
        }
        logger_1.Logger.notice('Adding and committing changes');
        yield git.add('.');
        yield git.commit(`Add Woo header to main plugin file and create changelog in ${base}`);
        logger_1.Logger.notice(`Pushing ${workingBranch} branch to Github`);
        yield git.push('origin', workingBranch);
        if (!commitDirectToBase) {
            logger_1.Logger.startTask('Creating a pull request');
            const pullRequest = yield (0, repo_1.createPullRequest)({
                owner,
                name,
                title: `Add Woo header to main plugin file and create changelog in ${base}`,
                body: `This PR adds the Woo header to the main plugin file and creates a changelog.txt file in ${base}.`,
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
