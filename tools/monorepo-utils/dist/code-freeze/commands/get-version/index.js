"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.getVersionCommand = void 0;
/**
 * External dependencies
 */
const extra_typings_1 = require("@commander-js/extra-typings");
const core_1 = require("@actions/core");
const chalk_1 = __importDefault(require("chalk"));
/**
 * Internal dependencies
 */
const logger_1 = require("../../../core/logger");
const environment_1 = require("../../../core/environment");
const index_1 = require("./lib/index");
const getRange = (override, between) => {
    if ((0, environment_1.isGithubCI)()) {
        logger_1.Logger.error('-b, --between option is not compatible with GitHub CI Output.');
        process.exit(1);
    }
    const today = (0, index_1.getToday)(override);
    const end = (0, index_1.getToday)(between);
    const versions = (0, index_1.getVersionsBetween)(today, end);
    logger_1.Logger.notice(chalk_1.default.greenBright.bold(`Releases Between ${today.toFormat('DDDD')} and ${end.toFormat('DDDD')}\n`));
    logger_1.Logger.table(['Version', 'Development Begins', 'Freeze', 'Release'], versions.map((v) => Object.values(v).map((d) => typeof d.toFormat === 'function'
        ? d.toFormat('EEE, MMM dd, yyyy')
        : d)));
    process.exit(0);
};
exports.getVersionCommand = new extra_typings_1.Command('get-version')
    .description('Get the release calendar for a given date')
    .option('-o, --override <override>', "Time Override: The time to use in checking whether the action should run (default: 'now').", 'now')
    .option('-b, --between <between>', 'When provided, instead of showing a single day, will show a releases in the range of <override> to <end>.')
    .action(({ override, between }) => {
    if (between) {
        return getRange(override, between);
    }
    const today = (0, index_1.getToday)(override);
    const acceleratedRelease = (0, index_1.getAcceleratedCycle)(today, false);
    const acceleratedDevelopment = (0, index_1.getAcceleratedCycle)(today);
    const monthlyRelease = (0, index_1.getMonthlyCycle)(today, false);
    const monthlyDevelopment = (0, index_1.getMonthlyCycle)(today);
    // Generate human-friendly output.
    logger_1.Logger.notice(chalk_1.default.greenBright.bold(`Release Calendar for ${today.toFormat('DDDD')}\n`));
    const table = [];
    // We're not in a release cycle on Wednesday.
    if (today.get('weekday') !== 3) {
        table.push([
            `${chalk_1.default.red('Accelerated Release Cycle')}`,
            acceleratedRelease.version,
            acceleratedRelease.begin.toFormat('EEE, MMM dd, yyyy'),
            acceleratedRelease.freeze.toFormat('EEE, MMM dd, yyyy'),
            acceleratedRelease.release.toFormat('EEE, MMM dd, yyyy'),
        ]);
    }
    table.push([
        `${chalk_1.default.red('Accelerated Development Cycle')}`,
        acceleratedDevelopment.version,
        acceleratedDevelopment.begin.toFormat('EEE, MMM dd, yyyy'),
        acceleratedDevelopment.freeze.toFormat('EEE, MMM dd, yyyy'),
        acceleratedDevelopment.release.toFormat('EEE, MMM dd, yyyy'),
    ]);
    // We're only in a release cycle if it is after the freeze day.
    if (today > monthlyRelease.freeze) {
        table.push([
            `${chalk_1.default.red('Monthly Release Cycle')}`,
            monthlyRelease.version,
            monthlyRelease.begin.toFormat('EEE, MMM dd, yyyy'),
            monthlyRelease.freeze.toFormat('EEE, MMM dd, yyyy'),
            monthlyRelease.release.toFormat('EEE, MMM dd, yyyy'),
        ]);
    }
    table.push([
        `${chalk_1.default.red('Monthly Development Cycle')}`,
        monthlyDevelopment.version,
        monthlyDevelopment.begin.toFormat('EEE, MMM dd, yyyy'),
        monthlyDevelopment.freeze.toFormat('EEE, MMM dd, yyyy'),
        monthlyDevelopment.release.toFormat('EEE, MMM dd, yyyy'),
    ]);
    logger_1.Logger.table(['', 'Version', 'Development Begins', 'Freeze', 'Release'], table);
    if ((0, environment_1.isGithubCI)()) {
        // For the machines.
        const isTodayAcceleratedFreeze = today.get('weekday') === 4;
        const isTodayMonthlyFreeze = +today === +monthlyDevelopment.begin;
        const monthlyVersionXY = monthlyRelease.version.substr(0, monthlyRelease.version.lastIndexOf('.'));
        (0, core_1.setOutput)('isTodayAcceleratedFreeze', isTodayAcceleratedFreeze ? 'yes' : 'no');
        (0, core_1.setOutput)('isTodayMonthlyFreeze', isTodayMonthlyFreeze ? 'yes' : 'no');
        (0, core_1.setOutput)('acceleratedVersion', acceleratedRelease.version);
        (0, core_1.setOutput)('monthlyVersion', monthlyRelease.version);
        (0, core_1.setOutput)('monthlyVersionXY', monthlyVersionXY);
        (0, core_1.setOutput)('releasesFrozenToday', JSON.stringify(Object.values(Object.assign(Object.assign({}, (isTodayMonthlyFreeze && {
            monthlyVersion: `${monthlyRelease.version} (Monthly)`,
        })), (isTodayAcceleratedFreeze && {
            aVersion: `${acceleratedRelease.version} (AF)`,
        })))));
        (0, core_1.setOutput)('acceleratedBranch', `release/${acceleratedRelease.version}`);
        (0, core_1.setOutput)('monthlyBranch', `release/${monthlyVersionXY}`);
        (0, core_1.setOutput)('monthlyMilestone', monthlyDevelopment.version);
        (0, core_1.setOutput)('acceleratedReleaseDate', acceleratedRelease.release.toISODate());
    }
    process.exit(0);
});
