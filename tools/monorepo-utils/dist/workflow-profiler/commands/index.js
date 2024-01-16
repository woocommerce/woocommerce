"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
/**
 * Internal dependencies
 */
const list_1 = __importDefault(require("./list"));
const profile_1 = __importDefault(require("./profile"));
const program = new extra_typings_1.Command('workflows')
    .description('Profile Github workflows')
    .addCommand(profile_1.default)
    .addCommand(list_1.default);
exports.default = program;
