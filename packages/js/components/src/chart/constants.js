// This is the max number of items that can be selected/shown on a chart at one time.
// If this number changes, the color scale also needs to be adjusted.
export const selectionLimit = 10;
export const colorScales = [
	[],
	[ 0.5 ],
	[ 0.333, 0.667 ],
	[ 0.2, 0.5, 0.8 ],
	[ 0.12, 0.375, 0.625, 0.88 ],
	[ 0, 0.25, 0.5, 0.75, 1 ],
	[ 0, 0.2, 0.4, 0.6, 0.8, 1 ],
	[ 0, 0.16, 0.32, 0.48, 0.64, 0.8, 1 ],
	[ 0, 0.14, 0.28, 0.42, 0.56, 0.7, 0.84, 1 ],
	[ 0, 0.12, 0.24, 0.36, 0.48, 0.6, 0.72, 0.84, 1 ],
	[ 0, 0.11, 0.22, 0.33, 0.44, 0.55, 0.66, 0.77, 0.88, 1 ],
];
