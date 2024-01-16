"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.octokitWithAuth = exports.graphqlWithAuth = void 0;
/**
 * External dependencies
 */
const graphql_1 = require("@octokit/graphql");
const octokit_1 = require("octokit");
/**
 * Internal dependencies
 */
const environment_1 = require("../environment");
let graphqlWithAuthInstance;
let octokitWithAuthInstance;
/**
 * Returns a graphql instance with auth headers, throws an Exception if
 * `GITHUB_TOKEN` env var is not present.
 *
 * @return graphql instance
 */
const graphqlWithAuth = () => {
    if (graphqlWithAuthInstance) {
        return graphqlWithAuthInstance;
    }
    graphqlWithAuthInstance = graphql_1.graphql.defaults({
        headers: {
            authorization: `Bearer ${(0, environment_1.getEnvVar)('GITHUB_TOKEN', true)}`,
        },
    });
    return graphqlWithAuthInstance;
};
exports.graphqlWithAuth = graphqlWithAuth;
/**
 * Returns an Octokit instance with auth headers, throws an Exception if
 * `GITHUB_TOKEN` env var is not present.
 *
 * @return graphql instance
 */
const octokitWithAuth = () => {
    if (octokitWithAuthInstance) {
        return octokitWithAuthInstance;
    }
    octokitWithAuthInstance = new octokit_1.Octokit({
        auth: (0, environment_1.getEnvVar)('GITHUB_TOKEN', true),
    });
    return octokitWithAuthInstance;
};
exports.octokitWithAuth = octokitWithAuth;
