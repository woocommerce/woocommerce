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
const logger_1 = require("../../../core/logger");
const program = new extra_typings_1.Command('list')
    .description('List all Github workflows in a repository')
    .option('-o --owner <owner>', 'Repository owner. Default: woocommerce', 'woocommerce')
    .option('-n --name <name>', 'Repository name. Default: woocommerce', 'woocommerce')
    .action(({ owner, name }) => __awaiter(void 0, void 0, void 0, function* () {
    logger_1.Logger.startTask('Listing all workflows');
    const allWorkflows = yield (0, data_1.getAllWorkflows)(owner, name);
    logger_1.Logger.notice(`There are ${allWorkflows.length} workflows in the repository.`);
    logger_1.Logger.table(['Workflow Name', 'configuration file', 'Id'], allWorkflows.map((workflow) => [
        workflow.name,
        workflow.path.replace('.github/workflows/', ''),
        workflow.id,
    ]));
    logger_1.Logger.endTask();
}));
exports.default = program;
