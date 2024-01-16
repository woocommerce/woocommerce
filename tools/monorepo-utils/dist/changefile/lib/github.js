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
exports.getChangelogDetailsError = exports.getChangelogDetails = exports.getChangelogComment = exports.getChangelogMessage = exports.getChangelogType = exports.getChangelogSignificance = exports.shouldAutomateChangelog = exports.getPullRequestData = void 0;
/**
 * Internal dependencies
 */
const repo_1 = require("../../core/github/repo");
const logger_1 = require("../../core/logger");
/**
 * Get relevant data from a pull request.
 *
 * @param {Object} options
 * @param {string} options.owner repository owner.
 * @param {string} options.name  repository name.
 * @param {string} prNumber      pull request number.
 * @return {Promise<object>}     pull request data.
 */
const getPullRequestData = (options, prNumber) => __awaiter(void 0, void 0, void 0, function* () {
    const { owner, name } = options;
    const prData = yield (0, repo_1.getPullRequest)({ owner, name, prNumber });
    const isCommunityPR = (0, repo_1.isCommunityPullRequest)(prData, owner, name);
    const headOwner = isCommunityPR ? prData.head.repo.owner.login : owner;
    const branch = prData.head.ref;
    const fileName = `${prNumber}-${branch.replace(/\//g, '-')}`;
    const prBody = prData.body;
    const head = prData.head.sha;
    const base = prData.base.sha;
    return {
        prBody,
        isCommunityPR,
        headOwner,
        branch,
        fileName,
        head,
        base,
    };
});
exports.getPullRequestData = getPullRequestData;
/**
 * Determine if a pull request description activates the changelog automation.
 *
 * @param {string} body pull request description.
 * @return {boolean} if the pull request description activates the changelog automation.
 */
const shouldAutomateChangelog = (body) => {
    const regex = /\[x\] Automatically create a changelog entry from the details/gm;
    return regex.test(body);
};
exports.shouldAutomateChangelog = shouldAutomateChangelog;
/**
 * Get the changelog significance from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog significance.
 */
const getChangelogSignificance = (body) => {
    const regex = /\[x\] (Patch|Minor|Major)\r\n/gm;
    const matches = body.match(regex);
    if (matches === null) {
        logger_1.Logger.error('No changelog significance found');
        // Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
        return;
    }
    if (matches.length > 1) {
        logger_1.Logger.error('Multiple changelog significances found. Only one can be entered');
        // Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
        return;
    }
    const significance = regex.exec(body);
    return significance[1].toLowerCase();
};
exports.getChangelogSignificance = getChangelogSignificance;
/**
 * Get the changelog type from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog type.
 */
const getChangelogType = (body) => {
    const regex = /\[x\] (Fix|Add|Update|Dev|Tweak|Performance|Enhancement) -/gm;
    const matches = body.match(regex);
    if (matches === null) {
        logger_1.Logger.error('No changelog type found');
        // Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
        return;
    }
    if (matches.length > 1) {
        logger_1.Logger.error('Multiple changelog types found. Only one can be entered');
        // Logger.error has a process.exit( 1 ) call, this return is purely for testing purposes.
        return;
    }
    const type = regex.exec(body);
    return type[1].toLowerCase();
};
exports.getChangelogType = getChangelogType;
/**
 * Get the changelog message from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog message.
 */
const getChangelogMessage = (body) => {
    const messageRegex = /#### Message ?(<!--(.*)-->)?(.*)#### Comment/gms;
    const match = messageRegex.exec(body);
    if (!match) {
        logger_1.Logger.error('No changelog message found');
    }
    let message = match[3].trim();
    // Newlines break the formatting of the changelog, so we replace them with spaces.
    message = message.replace(/\r\n|\n/g, ' ');
    return message;
};
exports.getChangelogMessage = getChangelogMessage;
/**
 * Get the changelog comment from a pull request description.
 *
 * @param {string} body pull request description.
 * @return {void|string} changelog comment.
 */
const getChangelogComment = (body) => {
    const commentRegex = /#### Comment ?(<!--(.*)-->)?(.*)<\/details>/gms;
    const match = commentRegex.exec(body);
    let comment = match ? match[3].trim() : '';
    // Newlines break the formatting of the changelog, so we replace them with spaces.
    comment = comment.replace(/\r\n|\n/g, ' ');
    return comment;
};
exports.getChangelogComment = getChangelogComment;
/**
 * Get the changelog details from a pull request description.
 *
 * @param {string} body Pull request description
 * @return {Object}     Changelog details
 */
const getChangelogDetails = (body) => {
    return {
        significance: (0, exports.getChangelogSignificance)(body),
        type: (0, exports.getChangelogType)(body),
        message: (0, exports.getChangelogMessage)(body),
        comment: (0, exports.getChangelogComment)(body),
    };
};
exports.getChangelogDetails = getChangelogDetails;
/**
 * Determine if a pull request description contains changelog input errors.
 *
 * @param {Object} details              changelog details.
 * @param {string} details.significance changelog significance.
 * @param {string} details.type         changelog type.
 * @param {string} details.message      changelog message.
 * @param {string} details.comment      changelog comment.
 * @return {string|null} error message, or null if none found
 */
const getChangelogDetailsError = ({ significance, type, message, comment, }) => {
    if (comment && message) {
        return 'Both a message and comment were found. Only one can be entered';
    }
    if (comment && significance !== 'patch') {
        return 'Only patch changes can have a comment. Please change the significance to patch or remove the comment';
    }
    if (!significance) {
        return 'No changelog significance found';
    }
    if (!type) {
        return 'No changelog type found';
    }
    if (!comment && !message) {
        return 'No changelog message or comment found';
    }
    return null;
};
exports.getChangelogDetailsError = getChangelogDetailsError;
