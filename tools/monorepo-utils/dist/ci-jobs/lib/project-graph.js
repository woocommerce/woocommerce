"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.buildProjectGraph = void 0;
/**
 * External dependencies
 */
const node_child_process_1 = require("node:child_process");
const node_path_1 = __importDefault(require("node:path"));
/**
 * Internal dependencies
 */
const config_1 = require("./config");
const package_file_1 = require("./package-file");
/**
 * Builds a dependency graph of all projects in the monorepo and returns the root node.
 */
function buildProjectGraph() {
    // Get the root of the monorepo.
    const monorepoRoot = node_path_1.default.join((0, node_child_process_1.execSync)('pnpm -w root', { encoding: 'utf-8' }), '..');
    // PNPM provides us with a flat list of all projects
    // in the workspace and their dependencies.
    const workspace = JSON.parse((0, node_child_process_1.execSync)('pnpm -r list --only-projects --json', { encoding: 'utf-8' }));
    // Start by building an object containing all of the nodes keyed by their project name.
    // This will let us link them together quickly by iterating through the list of
    // dependencies and adding the applicable nodes.
    const nodes = {};
    let rootNode;
    for (const project of workspace) {
        // Use a relative path to the project so that it's easier for us to work with
        const projectPath = project.path.replace(new RegExp(`^${monorepoRoot.replace(/\\/g, '\\\\')}${node_path_1.default.sep}?`), '');
        const packageFile = (0, package_file_1.loadPackage)(node_path_1.default.join(project.path, 'package.json'));
        const ciConfig = (0, config_1.parseCIConfig)(packageFile);
        const node = {
            name: project.name,
            path: projectPath,
            ciConfig,
            dependencies: [],
        };
        // The first entry that `pnpm list` returns is the workspace root.
        // This will be the root node of our graph.
        if (!rootNode) {
            rootNode = node;
        }
        nodes[project.name] = node;
    }
    // One thing to keep in mind is that, technically, our dependency graph has multiple roots.
    // Each package that has no dependencies is a "root", however, for simplicity, we will
    // add these root packages under the monorepo root in order to have a clean graph.
    // Since the monorepo root has no CI config this won't cause any problems.
    // Track this by recording all of the dependencies and removing them
    // from the rootless list if they are added as a dependency.
    const rootlessDependencies = workspace.map((project) => project.name);
    // Now we can scan through all of the nodes and hook them up to their respective dependency nodes.
    for (const project of workspace) {
        const node = nodes[project.name];
        if (project.dependencies) {
            for (const dependency in project.dependencies) {
                node.dependencies.push(nodes[dependency]);
            }
        }
        if (project.devDependencies) {
            for (const dependency in project.devDependencies) {
                node.dependencies.push(nodes[dependency]);
            }
        }
        // Mark any dependencies that have a dependent as not being rootless.
        // A rootless dependency is one that nothing depends on.
        for (const dependency of node.dependencies) {
            const index = rootlessDependencies.indexOf(dependency.name);
            if (index > -1) {
                rootlessDependencies.splice(index, 1);
            }
        }
    }
    // Track the rootless dependencies now that we have them.
    for (const rootless of rootlessDependencies) {
        // Don't add the root node as a dependency of itself.
        if (rootless === rootNode.name) {
            continue;
        }
        rootNode.dependencies.push(nodes[rootless]);
    }
    return rootNode;
}
exports.buildProjectGraph = buildProjectGraph;
