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
exports.milestoneCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const ora_1 = __importDefault(require("ora"));
/**
 * Internal dependencies
 */
const repo_1 = require("../../../core/github/repo");
const api_1 = require("../../../core/github/api");
const version_1 = require("../../../core/version");
const logger_1 = require("../../../core/logger");
exports.milestoneCommand = new extra_typings_1.Command('milestone')
    .description('Create a milestone')
    .option('-d --dryRun', 'Prepare the milestone but do not create it.')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-m --milestone <milestone>', 'Milestone to create. Next milestone is gathered from Github if none is supplied')
    .action((options) => __awaiter(void 0, void 0, void 0, function* () {
    var _a;
    const { owner, name, dryRun, milestone } = options;
    let nextMilestone;
    let nextReleaseVersion;
    if (milestone) {
        logger_1.Logger.warn(`Manually creating milestone ${milestone} in ${owner}/${name}`);
        nextMilestone = milestone;
    }
    else {
        const versionSpinner = (0, ora_1.default)('No milestone supplied, going off the latest release version').start();
        const latestReleaseVersion = yield (0, repo_1.getLatestGithubReleaseVersion)(options);
        versionSpinner.succeed();
        nextReleaseVersion = (0, version_1.WPIncrement)(latestReleaseVersion);
        nextMilestone = (0, version_1.WPIncrement)(nextReleaseVersion);
        logger_1.Logger.warn(`The latest release in ${owner}/${name} is version: ${latestReleaseVersion}`);
        logger_1.Logger.warn(`The next release in ${owner}/${name} will be version: ${nextReleaseVersion}`);
        logger_1.Logger.warn(`The next milestone in ${owner}/${name} will be: ${nextMilestone}`);
    }
    const milestoneSpinner = (0, ora_1.default)(`Creating a ${nextMilestone} milestone`).start();
    if (dryRun) {
        milestoneSpinner.succeed();
        logger_1.Logger.notice(`DRY RUN: Skipping actual creation of milestone ${nextMilestone}`);
        process.exit(0);
    }
    try {
        yield (0, api_1.octokitWithAuth)().request(`POST /repos/${owner}/${name}/milestones`, {
            title: nextMilestone,
        });
    }
    catch (e) {
        const milestoneAlreadyExistsError = (_a = e.response.data.errors) === null || _a === void 0 ? void 0 : _a.some((error) => error.code === 'already_exists');
        if (milestoneAlreadyExistsError) {
            milestoneSpinner.succeed();
            logger_1.Logger.notice(`Milestone ${nextMilestone} already exists in ${owner}/${name}`);
            process.exit(0);
        }
        else {
            milestoneSpinner.fail();
            logger_1.Logger.error(`\nFailed to create milestone ${nextMilestone} in ${owner}/${name}`);
            logger_1.Logger.error(e.response.data.message);
            process.exit(1);
        }
    }
    milestoneSpinner.succeed();
    logger_1.Logger.notice(`Successfully created milestone ${nextMilestone} in ${owner}/${name}`);
}));
