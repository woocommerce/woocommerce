import {clearAndFillInput, waitForSelectorWithoutThrow} from "../page-utils";

/**
 * Take a string name and generate the slug for it.
 * Example: 'My plugin' => 'my-plugin'
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
 * Log in a user to either standalone WordPress or WordPress.com.
 *
 * @param pageTitle The expected page title for the login screen
 * @param userField Username input selector
 * @param username User's username
 * @param passwordField Password input selector
 * @param password User's password
 * @param submitButton Login form submit button selector
 * @returns {Promise<void>}
 */
export const userLogin = async ( pageTitle, userField, username, passwordField, password, submitButton ) => {
	const isWPLogin = await waitForSelectorWithoutThrow( passwordField );
	if ( isWPLogin ) {
		await expect( page.title() ).resolves.toMatch( pageTitle );

		await clearAndFillInput( userField, ' ' );
		await page.type( userField, username );
		await page.type( passwordField, password );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( submitButton ),
		] );
	} else {

	}
};
