"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.logStepResults = exports.logJobResults = exports.logWorkflowRunResults = void 0;
/**
 * Internal dependencies
 */
const logger_1 = require("../../core/logger");
const math_1 = require("./math");
/**
 * Print workflow run results to the console.
 *
 * @param {string} name Workflow name
 * @param {Object} data Workflow run results
 */
const logWorkflowRunResults = (name, data) => {
    logger_1.Logger.table([
        'Workflow Name',
        'Total runs',
        'success',
        'failed',
        'cancelled',
        'average (min)',
        'median (min)',
        'longest (min)',
        'shortest (min)',
        '90th percentile (min)',
    ], [
        [
            name,
            data.count_items_available.toString(),
            data.success.toString(),
            data.failure.toString(),
            data.cancelled.toString(),
            ((0, math_1.calculateMean)(data.times) / 1000 / 60).toFixed(2), // in minutes,
            ((0, math_1.calculateMedian)(data.times) / 1000 / 60).toFixed(2), // in minutes
            (Math.max(...data.times) / 1000 / 60).toFixed(2), // in minutes
            (Math.min(...data.times) / 1000 / 60).toFixed(2), // in minutes
            ((0, math_1.calculate90thPercentile)(data.times) / 1000 / 60).toFixed(2), // in minutes
        ],
    ]);
};
exports.logWorkflowRunResults = logWorkflowRunResults;
/**
 * Log job data from a workflow run.
 *
 * @param {Object} data compiled job data
 */
const logJobResults = (data) => {
    const rows = Object.keys(data).map((jobName) => {
        const job = data[jobName];
        return [
            jobName,
            ((0, math_1.calculateMean)(job.times) / 1000 / 60).toFixed(2), // in minutes
            ((0, math_1.calculateMedian)(job.times) / 1000 / 60).toFixed(2), // in minutes
            (Math.max(...job.times) / 1000 / 60).toFixed(2), // in minutes
            (Math.min(...job.times) / 1000 / 60).toFixed(2), // in minutes
            ((0, math_1.calculate90thPercentile)(job.times) / 1000 / 60).toFixed(2), // in minutes
        ];
    });
    logger_1.Logger.table([
        'Job Name',
        'average (min)',
        'median (min)',
        'longest (min)',
        'shortest (min)',
        '90th percentile (min)',
    ], rows);
};
exports.logJobResults = logJobResults;
/**
 * Log job steps from a workflow run.
 *
 * @param {Object} data compiled job data
 */
const logStepResults = (data) => {
    Object.keys(data).forEach((jobName) => {
        const job = data[jobName];
        const rows = Object.keys(job.steps).map((stepName) => {
            const step = job.steps[stepName];
            return [
                stepName,
                ((0, math_1.calculateMean)(step) / 1000 / 60).toFixed(2), // in minutes
                ((0, math_1.calculateMedian)(step) / 1000 / 60).toFixed(2), // in minutes
                (Math.max(...step) / 1000 / 60).toFixed(2), // in minutes
                (Math.min(...step) / 1000 / 60).toFixed(2), // in minutes
                ((0, math_1.calculate90thPercentile)(step) / 1000 / 60).toFixed(2), // in minutes
            ];
        });
        logger_1.Logger.table([
            `Steps for job: ${jobName}`,
            'average (min)',
            'median (min)',
            'longest (min)',
            'shortest (min)',
            '90th percentile (min)',
        ], rows);
    });
};
exports.logStepResults = logStepResults;
