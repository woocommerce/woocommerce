"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.init = exports.settings = exports.name = exports.metadata = void 0;
/**
 * Internal dependencies
 */
const block_json_1 = __importDefault(require("./block.json"));
const edit_1 = require("./edit");
const utils_1 = require("../../../utils");
const { name, ...metadata } = block_json_1.default;
exports.name = name;
exports.metadata = metadata;
exports.settings = {
    example: {},
    edit: edit_1.Edit,
};
const init = () => (0, utils_1.registerProductEditorBlockType)({
    name,
    metadata: metadata,
    settings: exports.settings,
});
exports.init = init;
