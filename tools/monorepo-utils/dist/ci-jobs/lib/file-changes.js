"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getFileChanges = void 0;
/**
 * External dependencies
 */
const node_child_process_1 = require("node:child_process");
/**
 * Gets the project path for every project in the graph.
 *
 * @param {Object} graph The project graph to process.
 * @return {Object} The project paths keyed by the project name.
 */
function getProjectPaths(graph) {
    const projectPaths = {};
    const queue = [graph];
    const visited = {};
    while (queue.length > 0) {
        const node = queue.shift();
        if (!node) {
            continue;
        }
        if (visited[node.name]) {
            continue;
        }
        projectPaths[node.name] = node.path;
        visited[node.name] = true;
        queue.push(...node.dependencies);
    }
    return projectPaths;
}
/**
 * Checks the changed files and returns any that are relevant to the project.
 *
 * @param {string}         projectPath  The path to the project to get changed files for.
 * @param {Array.<string>} changedFiles The files that have changed in the repo.
 * @return {Array.<string>} The files that have changed in the project.
 */
function getChangedFilesForProject(projectPath, changedFiles) {
    const projectChanges = [];
    // Find all of the files that have changed in the project.
    for (const filePath of changedFiles) {
        if (!filePath.startsWith(projectPath)) {
            continue;
        }
        // Track the file relative to the project.
        projectChanges.push(filePath.slice(projectPath.length + 1));
    }
    return projectChanges;
}
/**
 * Pulls all of the files that have changed in the project graph since the given git ref.
 *
 * @param {Object} projectGraph The project graph to assign changes for.
 * @param {string} baseRef      The git ref to compare against for changes.
 * @return {Object} A map of changed files keyed by the project name.
 */
function getFileChanges(projectGraph, baseRef) {
    const projectPaths = getProjectPaths(projectGraph);
    // We're going to use git to figure out what files have changed.
    const output = (0, node_child_process_1.execSync)(`git diff --name-only ${baseRef}`, {
        encoding: 'utf8',
    });
    const changedFilePaths = output.split('\n');
    const changes = {};
    for (const projectName in projectPaths) {
        // Projects with no paths have no changed files for us to identify.
        if (!projectPaths[projectName]) {
            continue;
        }
        const projectChanges = getChangedFilesForProject(projectPaths[projectName], changedFilePaths);
        if (projectChanges.length === 0) {
            continue;
        }
        changes[projectName] = projectChanges;
    }
    return changes;
}
exports.getFileChanges = getFileChanges;
