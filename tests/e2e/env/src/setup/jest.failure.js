/** @format */
import {
	sendFailedTestScreenshotToSlack,
	sendFailedTestMessageToSlack,
} from '../slack';

import { bind } from 'jest-each';
const { takeScreenshotFor } = require( '../../utils' );

/**
 * Override the test case method so we can take screenshots of assertion failures.
 *
 * See: https://github.com/smooth-code/jest-puppeteer/issues/131#issuecomment-469439666
 */

/**
 * We need to reference the original version of Jest.
 */
const originalDescribe = global.describe;
const originalIt = global.it;

/**
 * A custom describe function that stores the name of the describe block.
 * @type {describe}
 */
global.describe = (() => {
	const describe = ( blockName, callback ) => {

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

/**
 * A custom it function that wraps the test function in a callback
 * which takes a screenshot on test failure.
 *
 * @type {function(*=, *=): *}
 */
global.it = (() => {
	const test = async ( testName, callback ) => {
		const testCallback = async () => screenshotTest( testName, callback );
		return originalIt( testName, testCallback );
	};
	const only = ( testName, callback ) => {
		return originalIt.only( testName, callback );
	};
	const skip = ( testName, callback ) => {
		return originalIt.skip( testName, callback );
	};

	test.each = bind( test, false );
	only.each = bind( only, false );
	skip.each = bind( skip, false );
	test.only = only;
	test.skip = skip;

	return test;
})();

/**
 * Save a screenshot during a test if the test fails.
 * @param testName
 * @param callback
 * @returns {Promise<void>}
 */
const screenshotTest = async ( testName, callback ) => {
	try {
		await callback();
	} catch ( e ) {
		const { title, filePath } = await takeScreenshotFor( testName );
		await sendFailedTestMessageToSlack( title );
		if ( filePath ) {
			await sendFailedTestScreenshotToSlack( filePath );
		}

		throw ( e );
	}
};
