/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import { ADMIN_STATE_PATH } from '../playwright.config';
import { wpCLI } from '../utils/cli';

export const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		await wpCLI(
			'wp option set woocommerce_feature_product_block_editor_enabled yes'
		);

		// Disable the product editor tour
		await restApi.post( 'wc-admin/options', {
			woocommerce_block_product_tour_shown: 'yes',
		} );

		await use( page );

		await wpCLI(
			'wp option set woocommerce_feature_product_block_editor_enabled no'
		);
	},
	storageState: ADMIN_STATE_PATH,
} );
