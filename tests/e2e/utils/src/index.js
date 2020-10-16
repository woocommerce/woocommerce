/*
 * External dependencies
 */
import allWPUtilities from '@wordpress/e2e-test-utils';
/*
 * Internal dependencies
 */
import allFlows from './flows';
import allComponents from './components';
import allUtilities from './page-utils';

module.exports = {
	...allWPUtilities,
	...allFlows,
	...allComponents,
	...allUtilities,
};
