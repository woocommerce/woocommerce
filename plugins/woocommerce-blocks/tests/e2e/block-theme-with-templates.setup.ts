/**
 * External dependencies
 */
import {
	BLOCK_THEME_WITH_TEMPLATES_SLUG,
	DB_EXPORT_FILE,
	cli,
} from '@woocommerce/e2e-utils';
import { test as setup, expect } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme with templates', async ( { requestUtils } ) => {
	await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

	const cliOutput = await cli(
		`npm run wp-env run tests-cli wp db export ${ DB_EXPORT_FILE }`
	);
	expect( cliOutput.stdout ).toContain( 'Success: Exported' );
} );
