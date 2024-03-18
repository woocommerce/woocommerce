/**
 * External dependencies
 */
import { BLOCK_THEME_WITH_TEMPLATES_SLUG } from '@woocommerce/e2e-utils';
import { test as setup } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme with templates', async ( { requestUtils } ) => {
	await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );
} );
