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
exports.getRunJobData = exports.getCompiledJobData = exports.getWorkflowRunData = exports.getWorkflowData = exports.getAllWorkflows = void 0;
/**
 * Internal dependencies
 */
const api_1 = require("../../core/github/api");
const logger_1 = require("../../core/logger");
const github_1 = require("./github");
const config_1 = __importDefault(require("../config"));
/**
 * Get all workflows from the WooCommerce repository.
 *
 * @param {string} owner - The owner of the repository.
 * @param {string} name  - The name of the repository.
 * @return Workflows and total count
 */
const getAllWorkflows = (owner, name) => __awaiter(void 0, void 0, void 0, function* () {
    const initialTotals = {
        count_items_processed: 0,
        count_items_available: 0,
        workflows: [],
    };
    const requestOptions = {
        owner,
        repo: name,
    };
    const endpoint = 'GET /repos/{owner}/{repo}/actions/workflows';
    const processPage = (data, totals) => {
        const { total_count, workflows } = data;
        totals.count_items_available = total_count;
        totals.count_items_processed += workflows.length;
        totals.workflows = totals.workflows.concat(workflows);
        return totals;
    };
    const totals = yield (0, github_1.requestPaginatedData)(initialTotals, endpoint, requestOptions, processPage);
    return totals.workflows;
});
exports.getAllWorkflows = getAllWorkflows;
/**
 * Handle on page of workflow runs.
 *
 * @param {Object} data   Github workflow run data
 * @param {Object} totals totals
 * @return {Object} totals
 */
const processWorkflowRunPage = (data, totals) => {
    const { workflow_runs, total_count } = data;
    if (total_count === 0) {
        return totals;
    }
    totals.count_items_available = total_count;
    totals.count_items_processed += workflow_runs.length;
    logger_1.Logger.notice(`Fetched workflows ${totals.count_items_processed} / ${totals.count_items_available}`);
    const { WORKFLOW_DURATION_CUTOFF_MINUTES } = config_1.default;
    workflow_runs.forEach((run) => {
        totals[run.conclusion]++;
        if (run.conclusion === 'success') {
            totals.nodeIds.push(run.node_id);
            const time = new Date(run.updated_at).getTime() -
                new Date(run.run_started_at).getTime();
            const maxDuration = 1000 * 60 * WORKFLOW_DURATION_CUTOFF_MINUTES;
            if (time < maxDuration) {
                totals.times.push(time);
            }
        }
    });
    return totals;
};
/**
 * Get workflow run data for a given workflow.
 *
 * @param {number} id Workflow id
 * @return {Object} Workflow data
 */
const getWorkflowData = (id, owner, name) => __awaiter(void 0, void 0, void 0, function* () {
    const { data } = yield (0, api_1.octokitWithAuth)().request('GET /repos/{owner}/{repo}/actions/workflows/{workflow_id}', {
        owner,
        repo: name,
        workflow_id: id,
    });
    return data;
});
exports.getWorkflowData = getWorkflowData;
/**
 * Get workflow run data for a given workflow.
 *
 * @param {Object} options       request options
 * @param {Object} options.id    workflow id
 * @param {Object} options.owner repo owner
 * @param {Object} options.name  repo name
 * @param {Object} options.start start date
 * @param {Object} options.end   end date
 * @return {Object} totals
 */
const getWorkflowRunData = (options) => __awaiter(void 0, void 0, void 0, function* () {
    const { id, start, end, owner, name } = options;
    const initialTotals = {
        count_items_available: 0,
        nodeIds: [],
        times: [],
        success: 0,
        failure: 0,
        cancelled: 0,
        skipped: 0,
        count_items_processed: 0,
    };
    const requestOptions = {
        owner,
        repo: name,
        workflow_id: id,
        created: `${start}..${end}`,
    };
    const workflowRunEndpoint = 'GET /repos/{owner}/{repo}/actions/workflows/{workflow_id}/runs';
    const totals = yield (0, github_1.requestPaginatedData)(initialTotals, workflowRunEndpoint, requestOptions, processWorkflowRunPage);
    return totals;
});
exports.getWorkflowRunData = getWorkflowRunData;
function splitArrayIntoChunks(array, n) {
    const chunks = [];
    for (let i = 0; i < array.length; i += n) {
        const chunk = array.slice(i, i + n);
        chunks.push(chunk);
    }
    return chunks;
}
/**
 * Get compiled job data for a given workflow run.
 *
 * @param {Object} jobData Workflow run data
 * @return {Object} Compiled job data
 */
const getCompiledJobData = (jobData, result = {}) => {
    const { nodes } = jobData;
    nodes.forEach((node) => {
        const jobs = node.checkSuite.checkRuns.nodes;
        jobs.forEach((job) => {
            const { name, startedAt, completedAt } = job;
            const time = new Date(completedAt).getTime() -
                new Date(startedAt).getTime();
            if (!result[name]) {
                result[name] = {
                    times: [],
                    steps: {},
                };
            }
            result[name].times.push(time);
            const steps = job.steps.nodes;
            steps.forEach((step) => {
                const { name: stepName, startedAt: stepStart, completedAt: stepCompleted, } = step;
                if (stepName === 'Set up job' ||
                    stepName === 'Complete job' ||
                    stepName.startsWith('Post ')) {
                    return;
                }
                const stepTime = new Date(stepCompleted).getTime() -
                    new Date(stepStart).getTime();
                if (!result[name].steps[stepName]) {
                    result[name].steps[stepName] = [];
                }
                result[name].steps[stepName].push(stepTime);
            });
        });
    });
    return result;
};
exports.getCompiledJobData = getCompiledJobData;
/**
 * Get data on individual workflow runs.
 *
 * @param {Array} nodeIds Workflow node ids
 * @return {Object} Workflow run data
 */
const getRunJobData = (nodeIds) => __awaiter(void 0, void 0, void 0, function* () {
    logger_1.Logger.notice(`Processing individual data for the ${nodeIds.length} successful workflow run(s)`);
    let compiledJobData = {};
    const perPage = 50;
    const gql = (0, api_1.graphqlWithAuth)();
    yield Promise.all(splitArrayIntoChunks(nodeIds, perPage).map((pageOfNodeIds, index) => __awaiter(void 0, void 0, void 0, function* () {
        logger_1.Logger.notice(`Fetched runs ${pageOfNodeIds.length === perPage
            ? (index + 1) * perPage
            : index * perPage + pageOfNodeIds.length} / ${nodeIds.length}`);
        const data = yield gql(`
				query($nodeIds: [ID!]!){ 
					nodes ( ids: $nodeIds ) {
					... on WorkflowRun {
						id
						workflow {
							id
							name
						}
						checkSuite {
							checkRuns ( first: 20, filterBy: { status: COMPLETED } ) {
									nodes {
										name
										id
										startedAt
										completedAt
										steps ( first: 50 ) {
											nodes {
												name
												startedAt
												completedAt
											}
										}
									}
								}
							}
						}
					}
				}
			`, {
            nodeIds: pageOfNodeIds,
        });
        compiledJobData = (0, exports.getCompiledJobData)(data, compiledJobData);
    })));
    return compiledJobData;
});
exports.getRunJobData = getRunJobData;
