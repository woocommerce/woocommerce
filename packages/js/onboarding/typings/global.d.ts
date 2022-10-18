import wpData from '@wordpress/data';

declare global {
	const wp: {
		data: typeof wpData
	};
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
