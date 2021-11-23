const path = require( 'path' );
const mkdirp = require( 'mkdirp' );
const { resolveLocalE2ePath } = require( './test-config' );

/**
 * Take a screenshot if browser context exists.
 *
 * @param message
 * @return {Promise<{filePath: string, title: string}|{filePath: *, title: *}>}
 */
const takeScreenshotFor = async ( message ) => {
	const title = message.replace( /\.$/, '' );
	const savePath = resolveLocalE2ePath( 'screenshots' );
	const filePath = path.join(
		savePath,
		`${ title }.png`.replace( /[^a-z0-9.-]+/gi, '-' )
	);

	mkdirp.sync( savePath );
	try {
		await page.screenshot( {
			path: filePath,
			fullPage: true,
		} );
	} catch ( error ) {
		return {
			title,
			filePath: '',
		};
	}
	return {
		title,
		filePath,
	};
};

module.exports = takeScreenshotFor;
