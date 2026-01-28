/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { test as baseTest } from './fixtures';
import type { RestApiClient } from './fixtures';
import { ADMIN_STATE_PATH } from '../playwright.config.js';
import { wpCLI } from '../utils/cli.js';

/**
 * Block editor fixtures interface.
 */
interface BlockEditorFixtures {
	page: Page;
	storageState: string;
}

export const test = baseTest.extend< BlockEditorFixtures >( {
	page: async ( { page, restApi }, use ): Promise< void > => {
		await wpCLI(
			'wp option set woocommerce_feature_product_block_editor_enabled yes'
		);

		// Disable the product editor tour
		await ( restApi as RestApiClient ).post( 'wc-admin/options', {
			woocommerce_block_product_tour_shown: 'yes',
		} );

		await use( page );

		await wpCLI(
			'wp option set woocommerce_feature_product_block_editor_enabled no'
		);
	},
	storageState: ADMIN_STATE_PATH,
} );
