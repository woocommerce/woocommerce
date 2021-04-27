const path = require( 'path' );
const mkdirp = require( 'mkdirp' );
const getAppRoot = require( './app-root' );

/**
 * Take a screenshot if browser context exists.
 * @param message
 * @returns {Promise<{filePath: string, title: string}|{filePath: *, title: *}>}
 */
const takeScreenshotFor = async ( message ) => {
	const title = message.replace( /\.$/, '' );
	const appPath = getAppRoot();
	const savePath = path.resolve( appPath, 'tests/e2e/screenshots' );
	const filePath = path.join(
		savePath,
		`${ title }.png`.replace( /[^a-z0-9.-]+/gi, '-' )
	);

	mkdirp.sync( savePath );
	try {
		await page.screenshot({
			path: filePath,
			fullPage: true,
		});
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
