/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import { WC_API_PATH } from '../utils/api-client';
import { ADMIN_STATE_PATH } from '../playwright.config';

export const test = baseTest.extend( {
	page: async ( { page, restApi }, use ) => {
		// Enable product block editor
		await restApi.put(
			`${ WC_API_PATH }/settings/advanced/woocommerce_feature_product_block_editor_enabled`,
			{
				value: 'yes',
			}
		);

		// Disable the product editor tour
		await restApi.post( 'wc-admin/options', {
			woocommerce_block_product_tour_shown: 'yes',
		} );

		await use( page );

		// Disable product block editor
		await restApi.put(
			`${ WC_API_PATH }/settings/advanced/woocommerce_feature_product_block_editor_enabled`,
			{
				value: 'no',
			}
		);
	},
	storageState: ADMIN_STATE_PATH,
} );
