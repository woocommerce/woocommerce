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
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
/**
 * Internal dependencies
 */
const data_1 = require("../../lib/data");
const log_1 = require("../../lib/log");
const logger_1 = require("../../../core/logger");
const program = new extra_typings_1.Command('profile')
    .description('Profile GitHub workflows')
    .argument('<start>', 'Start date in YYYY-MM-DD format')
    .argument('<end>', 'End date in YYYY-MM-DD format')
    .argument('<id>', 'Workflow Id or filename.')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .option('-s --show-steps')
    .action((start, end, id, { owner, name, showSteps }) => __awaiter(void 0, void 0, void 0, function* () {
    const workflowData = yield (0, data_1.getWorkflowData)(id, owner, name);
    logger_1.Logger.notice(`Processing workflow id ${id}: "${workflowData.name}" from ${start} to ${end}`);
    const workflowRunData = yield (0, data_1.getWorkflowRunData)({
        id,
        owner,
        name,
        start,
        end,
    });
    let runJobData = {};
    if (showSteps) {
        const { nodeIds } = workflowRunData;
        runJobData = yield (0, data_1.getRunJobData)(nodeIds);
    }
    (0, log_1.logWorkflowRunResults)(workflowData.name, workflowRunData);
    if (showSteps) {
        (0, log_1.logJobResults)(runJobData);
        (0, log_1.logStepResults)(runJobData);
    }
}));
exports.default = program;
