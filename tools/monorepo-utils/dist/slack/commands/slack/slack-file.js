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
exports.slackFileCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const web_api_1 = require("@slack/web-api");
const path_1 = require("path");
const fs_1 = require("fs");
/**
 * Internal dependencies
 */
const logger_1 = require("../../../core/logger");
exports.slackFileCommand = new extra_typings_1.Command('file')
    .description('Send a file upload message to a slack channel')
    .argument('<token>', 'Slack authentication token bearing required scopes.')
    .argument('<text>', 'Text based message to send to the slack channel.')
    .argument('<filePath>', 'File path to upload to the slack channel.')
    .argument('<channelIds...>', 'Slack channel IDs to send the message to. Pass as many as you like.')
    .option('--dont-fail', 'Do not fail the command if a message fails to send to any channel.')
    .option('--reply-ts <replyTs>', 'Reply to the message with the corresponding ts')
    .option('--filename <filename>', 'If provided, the filename that will be used for the file on Slack.')
    .action((token, text, filePath, channels, { dontFail, replyTs, filename }) => __awaiter(void 0, void 0, void 0, function* () {
    logger_1.Logger.startTask(`Attempting to send message to Slack for channels: ${channels.join(',')}`);
    const shouldFail = !dontFail;
    if (filePath && !(0, fs_1.existsSync)(filePath)) {
        logger_1.Logger.error(`Unable to open file with path: ${filePath}`, shouldFail);
    }
    const client = new web_api_1.WebClient(token);
    for (const channel of channels) {
        try {
            const requestOptions = {
                file: filePath,
                filename: filename ? filename : (0, path_1.basename)(filePath),
                channel_id: channel,
                initial_comment: text.replace(/\\n/g, '\n'),
                request_file_info: false,
                thread_ts: replyTs ? replyTs : null,
            };
            yield client.files.uploadV2(requestOptions);
            logger_1.Logger.notice(`Successfully uploaded ${filePath} to channel: ${channel}`);
        }
        catch (e) {
            if ('code' in e &&
                e.code === web_api_1.ErrorCode.PlatformError &&
                'message' in e &&
                e.message.includes('missing_scope')) {
                logger_1.Logger.error(`The provided token does not have the required scopes, please add files:write and chat:write to the token.`, shouldFail);
            }
            else {
                logger_1.Logger.error(e, shouldFail);
            }
        }
    }
    logger_1.Logger.endTask();
}));
