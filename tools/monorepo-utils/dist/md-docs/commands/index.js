"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
/**
 * Internal dependencies
 */
const create_1 = require("./manifest/create");
/**
 * Internal dependencies
 */
const program = new extra_typings_1.Command('md-docs')
    .description('Utilities for generating markdown doc manifests.')
    .addCommand(create_1.generateManifestCommand, { isDefault: true });
exports.default = program;
