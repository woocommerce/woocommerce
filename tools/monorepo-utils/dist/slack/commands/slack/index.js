"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
/**
 * Internal dependencies
 */
const slack_message_1 = require("./slack-message");
const slack_file_1 = require("./slack-file");
/**
 * Internal dependencies
 */
const program = new extra_typings_1.Command('slack')
    .description('Slack message sending utilities')
    .addCommand(slack_message_1.slackMessageCommand, { isDefault: true })
    .addCommand(slack_file_1.slackFileCommand);
exports.default = program;
