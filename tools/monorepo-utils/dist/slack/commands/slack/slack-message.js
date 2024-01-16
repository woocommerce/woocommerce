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
exports.slackMessageCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const core_1 = require("@actions/core");
/**
 * Internal dependencies
 */
const logger_1 = require("../../../core/logger");
const util_1 = require("../../../core/util");
const environment_1 = require("../../../core/environment");
exports.slackMessageCommand = new extra_typings_1.Command('message')
    .description('Send a plain-text message to a slack channel')
    .argument('<token>', 'Slack authentication token bearing required scopes.')
    .argument('<text>', 'Text based message to send to the slack channel.')
    .argument('<channels...>', 'Slack channels to send the message to. Pass as many as you like.')
    .option('--dont-fail', 'Do not fail the command if a message fails to send to any channel.')
    .action((token, text, channels, { dontFail }) => __awaiter(void 0, void 0, void 0, function* () {
    logger_1.Logger.startTask(`Attempting to send message to Slack for channels: ${channels.join(',')}`);
    const shouldFail = !dontFail;
    for (const channel of channels) {
        // Define the request options
        const options = {
            hostname: 'slack.com',
            path: '/api/chat.postMessage',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${token}`,
            },
        };
        try {
            const { statusCode, body } = yield (0, util_1.requestAsync)(options, JSON.stringify({
                channel,
                text: text.replace(/\\n/g, '\n'),
            }));
            logger_1.Logger.endTask();
            const response = JSON.parse(body);
            if (!response.ok || statusCode !== 200) {
                logger_1.Logger.error(`Slack API returned an error: ${response === null || response === void 0 ? void 0 : response.error}, message failed to send to ${channel}.`, shouldFail);
            }
            else {
                logger_1.Logger.notice(`Slack message sent successfully to channel: ${channel}`);
                if ((0, environment_1.isGithubCI)()) {
                    (0, core_1.setOutput)('ts', response.ts);
                }
            }
        }
        catch (e) {
            logger_1.Logger.error(e, shouldFail);
        }
    }
}));
