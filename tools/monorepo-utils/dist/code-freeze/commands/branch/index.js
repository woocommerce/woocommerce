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
exports.branchCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const semver_1 = require("semver");
const promptly_1 = require("promptly");
const chalk_1 = __importDefault(require("chalk"));
const ora_1 = __importDefault(require("ora"));
const core_1 = require("@actions/core");
/**
 * Internal dependencies
 */
const repo_1 = require("../../../core/github/repo");
const version_1 = require("../../../core/version");
const logger_1 = require("../../../core/logger");
const environment_1 = require("../../../core/environment");
const getNextReleaseBranch = (options) => __awaiter(void 0, void 0, void 0, function* () {
    const latestReleaseVersion = yield (0, repo_1.getLatestGithubReleaseVersion)(options);
    const nextReleaseVersion = (0, version_1.WPIncrement)(latestReleaseVersion);
    const parsedNextReleaseVersion = (0, semver_1.parse)(nextReleaseVersion);
    const nextReleaseMajorMinor = `${parsedNextReleaseVersion.major}.${parsedNextReleaseVersion.minor}`;
    return `release/${nextReleaseMajorMinor}`;
});
exports.branchCommand = new extra_typings_1.Command('branch')
    .description('Create a new release branch')
    .option('-d --dryRun', 'Prepare the branch but do not create it.')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-b --branch <branch>', 'Release branch to create. The branch will be determined from Github if none is supplied')
    .option('-s --source <source>', 'Branch to create the release branch from. Default: trunk', 'trunk')
    .action((options) => __awaiter(void 0, void 0, void 0, function* () {
    const { source, branch, owner, name, dryRun } = options;
    const isGithub = (0, environment_1.isGithubCI)();
    let nextReleaseBranch;
    if (!branch) {
        const versionSpinner = (0, ora_1.default)(chalk_1.default.yellow('No branch supplied, going off the latest release version')).start();
        nextReleaseBranch = yield getNextReleaseBranch(options);
        logger_1.Logger.warn(`The next release branch is ${nextReleaseBranch}`);
        versionSpinner.succeed();
    }
    else {
        nextReleaseBranch = branch;
    }
    const branchSpinner = (0, ora_1.default)(chalk_1.default.yellow(`Check to see if branch ${nextReleaseBranch} exists on ${owner}/${name}`)).start();
    const branchExists = yield (0, repo_1.doesGithubBranchExist)(options, nextReleaseBranch);
    branchSpinner.succeed();
    if (branchExists) {
        if (isGithub) {
            logger_1.Logger.error(`Release branch ${nextReleaseBranch} already exists`);
            // When in Github Actions, we don't want to prompt the user for input.
            process.exit(0);
        }
        const deleteExistingReleaseBranch = yield (0, promptly_1.confirm)(chalk_1.default.yellow(`Release branch ${nextReleaseBranch} already exists on ${owner}/${name}, do you want to delete it and create a new one from ${source}? [y/n]`));
        if (deleteExistingReleaseBranch) {
            if (!dryRun) {
                const deleteBranchSpinner = (0, ora_1.default)(chalk_1.default.yellow(`Delete branch ${nextReleaseBranch} on ${owner}/${name} and create new one from ${source}`)).start();
                yield (0, repo_1.deleteGithubBranch)(options, nextReleaseBranch);
                deleteBranchSpinner.succeed();
            }
        }
        else {
            logger_1.Logger.notice(`Branch ${nextReleaseBranch} already exist on ${owner}/${name}, no action taken.`);
            process.exit(0);
        }
    }
    const createBranchSpinner = (0, ora_1.default)(chalk_1.default.yellow(`Create branch ${nextReleaseBranch}`)).start();
    if (dryRun) {
        createBranchSpinner.succeed();
        logger_1.Logger.notice(`DRY RUN: Skipping actual creation of branch ${nextReleaseBranch} on ${owner}/${name}`);
        process.exit(0);
    }
    const ref = yield (0, repo_1.getRefFromGithubBranch)(options, source);
    yield (0, repo_1.createGithubBranch)(options, nextReleaseBranch, ref);
    createBranchSpinner.succeed();
    if (isGithub) {
        (0, core_1.setOutput)('nextReleaseBranch', nextReleaseBranch);
    }
    logger_1.Logger.notice(`Branch ${nextReleaseBranch} successfully created on ${owner}/${name}`);
}));
