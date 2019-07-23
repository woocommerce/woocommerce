/** format */

/**
 * Increase the default timeout to 10s
 */
let jestTimeoutInMilliSeconds = 10000;
jest.setTimeout( jestTimeoutInMilliSeconds );

/**
 * Extend expect to have the toMatchImageSnapshop function
 */
const { toMatchImageSnapshot } = require('jest-image-snapshot');
expect.extend({ toMatchImageSnapshot });
