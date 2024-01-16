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
exports.changelogCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const child_process_1 = require("child_process");
/**
 * Internal dependencies
 */
const logger_1 = require("../../../core/logger");
const git_1 = require("../../../core/git");
const lib_1 = require("./lib");
exports.changelogCommand = new extra_typings_1.Command('changelog')
    .description('Make changelog pull requests to trunk and release branch')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-d --dev-repo-path <devRepoPath>', 'Path to existing repo. Use this option to avoid cloning a fresh repo for development purposes. Note that using this option assumes dependencies are already installed.')
    .option('-c --commit-direct-to-base', 'Commit directly to the base branch. Do not create a PR just push directly to base branch', false)
    .option('-o, --override <override>', "Time Override: The time to use in checking whether the action should run (default: 'now').", 'now')
    .requiredOption('-v, --version <version>', 'Version to bump to')
    .action((options) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name, version, devRepoPath } = options;
    logger_1.Logger.startTask(`Making a temporary clone of '${owner}/${name}'`);
    const cloneOptions = {
        owner: owner ? owner : 'woocommerce',
        name: name ? name : 'woocommerce',
    };
    // Use a supplied path, otherwise do a full clone of the repo, including history so that changelogs can be created with links to PRs.
    const tmpRepoPath = devRepoPath
        ? devRepoPath
        : yield (0, git_1.cloneAuthenticatedRepo)(cloneOptions, false);
    logger_1.Logger.endTask();
    logger_1.Logger.notice(`Temporary clone of '${owner}/${name}' created at ${tmpRepoPath}`);
    // When a devRepoPath is provided, assume that the dependencies are already installed.
    if (!devRepoPath) {
        logger_1.Logger.notice(`Installing dependencies in ${tmpRepoPath}`);
        (0, child_process_1.execSync)('pnpm install --filter woocommerce', {
            cwd: tmpRepoPath,
            stdio: 'inherit',
        });
    }
    const releaseBranch = `release/${version}`;
    // Update the release branch.
    const releaseBranchChanges = yield (0, lib_1.updateReleaseBranchChangelogs)(options, tmpRepoPath, releaseBranch);
    // Update trunk.
    yield (0, lib_1.updateTrunkChangelog)(options, tmpRepoPath, releaseBranch, releaseBranchChanges);
}));
