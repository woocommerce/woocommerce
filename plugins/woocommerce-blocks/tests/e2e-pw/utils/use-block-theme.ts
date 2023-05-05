/**
 * External dependencies
 */
import { test as Test } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { BLOCK_THEME_SLUG } from './constants';

export const useBlockTheme = ( test: typeof Test ) => {
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
	} );
};
