/** @format */
/*
import {
	sendFailedTestScreenshotToSlack,
	sendFailedTestMessageToSlack
} from "./lib/reporter/slack-reporter";
*/
const path = require( 'path' );
const mkdirp = require( 'mkdirp' );
import { bind } from 'jest-each';
const { getAppRoot } = require( '../../utils' );

/**
 * Override the test case method so we can take screenshots of assertion failures.
 *
 * See: https://github.com/smooth-code/jest-puppeteer/issues/131#issuecomment-469439666
 */
let currentBlock;

/**
 * We need to reference the original version of Jest.
 */
const originalDescribe = global.describe;
const originalIt = global.it;

global.describe = (() => {
	const describe = ( blockName, callback ) => {
		currentBlock = blockName;

		try {
			originalDescribe( blockName, callback );
		} catch ( e ) {
			throw e;
		}

	};
	const only = ( blockName, callback ) => {
		originalDescribe.only( blockName, callback );
	};
	const skip = ( blockName, callback ) => {
		originalDescribe.skip( blockName, callback );
	};

	describe.each = bind( describe, false );
	only.each = bind( only, false );
	skip.each = bind( skip, false );
	describe.only = only;
	describe.skip = skip;

	return describe;
})();

global.it = (() => {
	const test = async ( testName, callback ) => {
		const testCallback = async () => screenshotTest( testName, callback );
		return originalIt( testName, testCallback );
	};
	const only = ( blockName, callback ) => {
		return originalIt.only( blockName, callback );
	};
	const skip = ( blockName, callback ) => {
		return originalIt.skip( blockName, callback );
	};

	test.each = bind( test, false );
	only.each = bind( only, false );
	skip.each = bind( skip, false );
	test.only = only;
	test.skip = skip;

	return test;
})();

const screenshotTest = async ( testName, callback ) => {
	try {
		await callback();
	} catch ( e ) {
		const testTitle = `${ currentBlock } - ${ testName }`.replace( /\.$/, '' );
		const appPath = getAppRoot();
		const savePath = path.resolve( appPath, 'tests/e2e/screenshots' );
		const filePath = path.join(
			savePath,
			`${ testTitle }.png`.replace( /[^a-z0-9.-]+/gi, '-' )
		);

		mkdirp.sync( savePath );
		await page.screenshot( {
			path: filePath,
			fullPage: true,
		} );

/*
		await sendFailedTestMessageToSlack( testTitle );
		await sendFailedTestScreenshotToSlack( filePath );
*/

		throw ( e );
	}
};
