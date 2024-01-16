"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.getVersionsBetween = exports.getAcceleratedCycle = exports.getMonthlyCycle = exports.getSecondTuesday = exports.getToday = exports.DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE = void 0;
/**
 * External dependencies
 */
const luxon_1 = require("luxon");
exports.DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE = 19;
/**
 * Get a DateTime object of now or the override time when specified. DateTime is normalized to start of day.
 *
 * @param {string} now The time to use in checking if today is the day of the code freeze. Default to now. Supports ISO formatted dates or 'now'.
 *
 * @return {DateTime} The DateTime object of now or the override time when specified.
 */
const getToday = (now = 'now') => {
    const today = now === 'now'
        ? luxon_1.DateTime.now().setZone('utc')
        : luxon_1.DateTime.fromISO(now, { zone: 'utc' });
    if (isNaN(today.toMillis())) {
        throw new Error('Invalid date: Check the override parameter (-o, --override) is a correct ISO formatted string or "now"');
    }
    return today.set({ hour: 0, minute: 0, second: 0, millisecond: 0 });
};
exports.getToday = getToday;
/**
 * Get the second Tuesday of the month, given a DateTime.
 *
 * @param {DateTime} when A DateTime object.
 *
 * @return {DateTime} The second Tuesday of the month contained in the input.
 */
const getSecondTuesday = (when) => {
    const year = when.get('year');
    const month = when.get('month');
    const firstDayOfMonth = luxon_1.DateTime.utc(year, month, 1);
    const dayOfWeek = firstDayOfMonth.get('weekday');
    const secondTuesday = dayOfWeek <= 2 ? 10 - dayOfWeek : 17 - dayOfWeek;
    return luxon_1.DateTime.utc(year, month, secondTuesday);
};
exports.getSecondTuesday = getSecondTuesday;
const getMonthlyCycle = (when, development = true) => {
    // July 12, 2023 is the start-point for 8.0.0, all versions follow that starting point.
    const startTime = luxon_1.DateTime.fromObject({
        year: 2023,
        month: 7,
        day: 12,
        hour: 0,
        minute: 0,
    }, { zone: 'UTC' });
    const currentMonthRelease = (0, exports.getSecondTuesday)(when);
    const nextMonthRelease = (0, exports.getSecondTuesday)(currentMonthRelease.plus({ months: 1 }));
    const release = when <= currentMonthRelease ? currentMonthRelease : nextMonthRelease;
    const previousRelease = (0, exports.getSecondTuesday)(release.minus({ days: exports.DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE + 2 }));
    const nextRelease = (0, exports.getSecondTuesday)(release.plus({ months: 1 }));
    const freeze = release.minus({
        days: exports.DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE + 1,
    });
    const monthNumber = (previousRelease.get('year') - startTime.get('year')) * 12 +
        previousRelease.get('month') -
        startTime.get('month');
    const version = ((80 + monthNumber) / 10).toFixed(1) + '.0';
    if (development) {
        if (when > freeze) {
            return (0, exports.getMonthlyCycle)(nextRelease, false);
        }
    }
    const begin = previousRelease.minus({
        days: exports.DAYS_BETWEEN_CODE_FREEZE_AND_RELEASE,
    });
    return {
        version,
        begin,
        freeze,
        release,
    };
};
exports.getMonthlyCycle = getMonthlyCycle;
/**
 * Get version and all dates / related to an accelerated cycle.
 *
 * @param {DateTime} when        A DateTime object.
 * @param {boolean}  development When true, the active development cycle will be returned, otherwise the active release cycle.
 * @return {Object} An object containing version and dates for a release.
 */
const getAcceleratedCycle = (when, development = true) => {
    if (!development) {
        when = when.minus({ week: 1 });
    }
    const dayOfWeek = when.get('weekday');
    const daysTilWednesday = dayOfWeek < 4 ? 3 - dayOfWeek : 10 - dayOfWeek;
    const freeze = when.plus({ days: daysTilWednesday });
    const lastAccelerated = freeze.minus({ days: 1 });
    const release = freeze.plus({ days: 6 });
    const begin = freeze.minus({ days: 6 });
    const currentMonthRelease = (0, exports.getSecondTuesday)(lastAccelerated);
    const nextMonthRelease = (0, exports.getSecondTuesday)(currentMonthRelease.plus({ months: 1 }));
    const monthlyRelease = freeze <= currentMonthRelease ? currentMonthRelease : nextMonthRelease;
    const monthlyCycle = (0, exports.getMonthlyCycle)(monthlyRelease, false);
    const previousMonthlyRelease = (0, exports.getSecondTuesday)(monthlyRelease.minus({ days: 28 }));
    const aVersion = 10 *
        (lastAccelerated.diff(previousMonthlyRelease, 'weeks').toObject()
            .weeks +
            1);
    const version = `${monthlyCycle.version}.${aVersion}`;
    return {
        version,
        begin,
        freeze,
        release,
    };
};
exports.getAcceleratedCycle = getAcceleratedCycle;
const getVersionsBetween = (start, end) => {
    if (start > end) {
        return (0, exports.getVersionsBetween)(end, start);
    }
    const versions = {};
    for (let i = start; i < end; i = i.plus({ days: 28 })) {
        const monthly = (0, exports.getMonthlyCycle)(i, false);
        versions[monthly.version] = monthly;
    }
    for (let i = start; i < end; i = i.plus({ days: 7 })) {
        const accelerated = (0, exports.getAcceleratedCycle)(i, false);
        versions[accelerated.version] = accelerated;
    }
    return Object.values(versions);
};
exports.getVersionsBetween = getVersionsBetween;
