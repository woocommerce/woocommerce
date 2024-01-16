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
exports.createJobsForChanges = void 0;
const test_environment_1 = require("./test-environment");
/**
 * Checks the config against the changes and creates one if it should be run.
 *
 * @param {string}         projectName The name of the project that the job is for.
 * @param {Object}         config      The config object for the lint job.
 * @param {Array.<string>} changes     The file changes that have occurred for the project.
 * @return {Object|null} The job that should be run or null if no job should be run.
 */
function createLintJob(projectName, config, changes) {
    let triggered = false;
    // Projects can configure jobs to be triggered when a
    // changed file matches a path regex.
    for (const file of changes) {
        for (const change of config.changes) {
            if (change.test(file)) {
                triggered = true;
                break;
            }
        }
        if (triggered) {
            break;
        }
    }
    if (!triggered) {
        return null;
    }
    return {
        projectName,
        command: config.command,
    };
}
/**
 * Checks the config against the changes and creates one if it should be run.
 *
 * @param {string}         projectName The name of the project that the job is for.
 * @param {Object}         config      The config object for the test job.
 * @param {Array.<string>} changes     The file changes that have occurred for the project.
 * @param {Array.<string>} cascadeKeys The cascade keys that have been triggered in dependencies.
 * @return {Promise.<Object|null>} The job that should be run or null if no job should be run.
 */
function createTestJob(projectName, config, changes, cascadeKeys) {
    return __awaiter(this, void 0, void 0, function* () {
        let triggered = false;
        // Some jobs can be configured to trigger when a dependency has a job that
        // was triggered. For example, a code change in a dependency might mean
        // that code is impacted in the current project even if no files were
        // actually changed in this project.
        if (config.cascadeKeys &&
            config.cascadeKeys.some((value) => cascadeKeys.includes(value))) {
            triggered = true;
        }
        // Projects can configure jobs to be triggered when a
        // changed file matches a path regex.
        if (!triggered) {
            for (const file of changes) {
                for (const change of config.changes) {
                    if (change.test(file)) {
                        triggered = true;
                        break;
                    }
                }
                if (triggered) {
                    break;
                }
            }
        }
        if (!triggered) {
            return null;
        }
        const createdJob = {
            projectName,
            name: config.name,
            command: config.command,
        };
        // We want to make sure that we're including the configuration for
        // any test environment that the job will need in order to run.
        if (config.testEnv) {
            createdJob.testEnv = {
                start: config.testEnv.start,
                envVars: yield (0, test_environment_1.parseTestEnvConfig)(config.testEnv.config),
            };
        }
        return createdJob;
    });
}
/**
 * Recursively checks the project for any jobs that should be executed and returns them.
 *
 * @param {Object}         node         The current project node to examine.
 * @param {Object}         changedFiles The files that have changed for the project.
 * @param {Array.<string>} cascadeKeys  The cascade keys that have been triggered in dependencies.
 * @return {Promise.<Object>} The jobs that have been created for the project.
 */
function createJobsForProject(node, changedFiles, cascadeKeys) {
    var _a, _b;
    return __awaiter(this, void 0, void 0, function* () {
        // We're going to traverse the project graph and check each node for any jobs that should be triggered.
        const newJobs = {
            lint: [],
            test: [],
        };
        // In order to simplify the way that cascades work we're going to recurse depth-first and check our dependencies
        // for jobs before ourselves. This lets any cascade keys created in dependencies cascade to dependents.
        const newCascadeKeys = [];
        for (const dependency of node.dependencies) {
            // Each dependency needs to have its own cascade keys so that they don't cross-contaminate.
            const dependencyCascade = [...cascadeKeys];
            const dependencyJobs = yield createJobsForProject(dependency, changedFiles, dependencyCascade);
            newJobs.lint.push(...dependencyJobs.lint);
            newJobs.test.push(...dependencyJobs.test);
            // Track any new cascade keys added by the dependency.
            // Since we're filtering out duplicates after the
            // dependencies are checked we don't need to
            // worry about their presence right now.
            newCascadeKeys.push(...dependencyCascade);
        }
        // Now that we're done looking at the dependencies we can add the cascade keys that
        // they created. Make sure to avoid adding duplicates so that we don't waste time
        // checking the same keys multiple times when we create the jobs.
        cascadeKeys.push(...newCascadeKeys.filter((value) => !cascadeKeys.includes(value)));
        // Projects that don't have any CI configuration don't have any potential jobs for us to check for.
        if (!node.ciConfig) {
            return newJobs;
        }
        for (const jobConfig of node.ciConfig.jobs) {
            switch (jobConfig.type) {
                case "lint" /* JobType.Lint */: {
                    const created = createLintJob(node.name, jobConfig, (_a = changedFiles[node.name]) !== null && _a !== void 0 ? _a : []);
                    if (!created) {
                        break;
                    }
                    newJobs.lint.push(created);
                    break;
                }
                case "test" /* JobType.Test */: {
                    const created = yield createTestJob(node.name, jobConfig, (_b = changedFiles[node.name]) !== null && _b !== void 0 ? _b : [], cascadeKeys);
                    if (!created) {
                        break;
                    }
                    newJobs.test.push(created);
                    // We need to track any cascade keys that this job is associated with so that
                    // dependent projects can trigger jobs with matching keys. We are expecting
                    // the array passed to this function to be modified by reference so this
                    // behavior is intentional.
                    if (jobConfig.cascadeKeys) {
                        cascadeKeys.push(...jobConfig.cascadeKeys);
                    }
                    break;
                }
            }
        }
        return newJobs;
    });
}
/**
 * Creates jobs to run for the given project graph and file changes.
 *
 * @param {Object} root    The root node for the project graph.
 * @param {Object} changes The file changes that have occurred.
 * @return {Promise.<Object>} The jobs that should be run.
 */
function createJobsForChanges(root, changes) {
    return createJobsForProject(root, changes, []);
}
exports.createJobsForChanges = createJobsForChanges;
