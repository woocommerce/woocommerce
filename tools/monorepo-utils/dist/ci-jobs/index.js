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
const logger_1 = require("../core/logger");
const project_graph_1 = require("./lib/project-graph");
const file_changes_1 = require("./lib/file-changes");
const job_processing_1 = require("./lib/job-processing");
const program = new extra_typings_1.Command('ci-jobs')
    .description('Generates CI workflow jobs based on the changes since the base ref.')
    .argument('<base-ref>', 'Base ref to compare the current ref against for change detection.')
    .action((baseRef) => __awaiter(void 0, void 0, void 0, function* () {
    const projectGraph = (0, project_graph_1.buildProjectGraph)();
    const fileChanges = (0, file_changes_1.getFileChanges)(projectGraph, baseRef);
    const jobs = (0, job_processing_1.createJobsForChanges)(projectGraph, fileChanges);
    logger_1.Logger.notice(JSON.stringify(jobs, null, '\\t'));
}));
exports.default = program;
