/**
 * Hardcoded data for the Notices analytics dashboard mock. Replaced with real
 * REST data once the design pass is signed off.
 */

const DAY_MS = 24 * 60 * 60 * 1000;

/**
 * Build a daily timeseries running up to today, in the shape `<Chart>` from
 * `@woocommerce/components` expects: an array of per-date objects keyed by
 * series name, each value being `{ label, value }`.
 *
 * @param {string}   seriesKey   The series key (used as the object key on each row).
 * @param {string}   seriesLabel Human-readable series label shown in tooltips/legend.
 * @param {number[]} dailyValues Array of daily values, oldest first.
 * @return {Array<Object>} Chart-ready data points.
 */
function buildChartSeries( seriesKey, seriesLabel, dailyValues ) {
	const today = new Date();
	today.setUTCHours( 0, 0, 0, 0 );
	const start = today.getTime() - ( dailyValues.length - 1 ) * DAY_MS;

	return dailyValues.map( ( value, idx ) => {
		const isoDate = new Date( start + idx * DAY_MS )
			.toISOString()
			.slice( 0, 19 );
		return {
			date: isoDate,
			[ seriesKey ]: { label: seriesLabel, value },
		};
	} );
}

export const summaryStats = {
	notifications: {
		sentLastMonth: 320,
		sentToday: 49,
		queued: 192,
	},
	signups: {
		signedUpLastMonth: 511,
		signedUpToday: 16,
	},
};

export const notificationsTimeseries = buildChartSeries(
	'notifications',
	'Notifications sent',
	[ 17, 9, 12, 6, 13, 28, 36, 21, 8, 14, 30, 19, 11, 7, 23 ]
);

export const signupsTimeseries = buildChartSeries(
	'signups',
	'Sign-ups',
	[ 20, 10, 15, 8, 16, 34, 42, 25, 11, 18, 36, 23, 14, 9, 28 ]
);

export const mostWanted = [
	{ productId: 101, name: 'Bow Tie Bundle', customers: 439 },
	{ productId: 102, name: 'Belt', customers: 390 },
	{ productId: 103, name: 'Tshirt', customers: 345 },
	{ productId: 104, name: 'White Socks', customers: 70 },
	{ productId: 105, name: 'Polo', customers: 38 },
];

export const mostOverdue = [
	{ productId: 201, name: 'Vneck Tshirt', days: 112 },
	{ productId: 202, name: 'Beanie', days: 101 },
	{ productId: 203, name: 'Hoodie with Pocket', days: 100 },
	{ productId: 204, name: 'Light Blue T-Shirt', days: 88 },
	{ productId: 205, name: 'Brown Polo', days: 47 },
];

export const mostSignedUpByWindow = {
	week: [
		{ productId: 301, name: 'Light Blue T-Shirt', customers: 312 },
		{ productId: 302, name: 'Bowtie', customers: 287 },
		{ productId: 303, name: 'Bow Tie Bundle', customers: 201 },
		{ productId: 304, name: 'Sunglasses', customers: 174 },
		{ productId: 305, name: 'Long Sleeve Tee', customers: 138 },
	],
	month: [
		{ productId: 301, name: 'Light Blue T-Shirt', customers: 1284 },
		{ productId: 302, name: 'Bowtie', customers: 1133 },
		{ productId: 303, name: 'Bow Tie Bundle', customers: 800 },
		{ productId: 304, name: 'Sunglasses', customers: 680 },
		{ productId: 305, name: 'Long Sleeve Tee', customers: 555 },
	],
	quarter: [
		{ productId: 301, name: 'Light Blue T-Shirt', customers: 3812 },
		{ productId: 302, name: 'Bowtie', customers: 3340 },
		{ productId: 303, name: 'Bow Tie Bundle', customers: 2401 },
		{ productId: 304, name: 'Sunglasses', customers: 2017 },
		{ productId: 305, name: 'Long Sleeve Tee', customers: 1640 },
	],
};
