"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
/**
 * Internal dependencies
 */
const get_version_1 = require("./get-version");
const milestone_1 = require("./milestone");
const branch_1 = require("./branch");
const version_bump_1 = require("./version-bump");
const changelog_1 = require("./changelog");
const accelerated_prep_1 = require("./accelerated-prep");
const program = new extra_typings_1.Command('code-freeze')
    .description('Code freeze utilities')
    .addCommand(get_version_1.getVersionCommand)
    .addCommand(milestone_1.milestoneCommand)
    .addCommand(branch_1.branchCommand)
    .addCommand(version_bump_1.versionBumpCommand)
    .addCommand(changelog_1.changelogCommand)
    .addCommand(accelerated_prep_1.acceleratedPrepCommand);
exports.default = program;
