/**
 * Take a string name and generate the slug for it.
 * Example: 'My plugin' => 'my-plugin'
 * @param text string to convert to a slug
 *
 * Sourced from: https://gist.github.com/spyesx/561b1d65d4afb595f295
 **/
 export const getSlug = ( text ) => {
	text = text.trim().toLowerCase();

	// remove accents, swap ñ for n, etc
	const from = 'åàáãäâèéëêìíïîòóöôùúüûñç·/_,:;';
	const to = 'aaaaaaeeeeiiiioooouuuunc------';

	for (let i = 0, l = from.length; i < l; i++) {
		text = text.replace(new RegExp(from.charAt(i), "g"), to.charAt(i));
	}

	return text
		.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
		.replace(/\s+/g, '-') // collapse whitespace and replace by -
		.replace(/-+/g, '-') // collapse dashes
		.replace(/^-+/, '') // trim - from start of text
		.replace(/-+$/, '') // trim - from end of text
		.replace(/-/g, '-');
};

// Conditionally determine whether or not to skip a test suite
export const describeIf = ( condition ) =>
	condition ? describe : describe.skip;

// Conditionally determine whether or not to skip a test case
export const itIf = ( condition ) =>
	condition ? it : it.skip;

/**
 * Wait for a timeout in milliseconds
 * @param timeout delay time in milliseconds
 * @returns {Promise<void>}
 */
export const waitForTimeout = async ( timeout ) => {
	await new Promise( ( resolve ) => setTimeout( resolve, timeout ) );
};
