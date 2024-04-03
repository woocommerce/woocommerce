/**
 * External dependencies
 */
import { BLOCK_THEME_WITH_TEMPLATES_SLUG, cli } from '@woocommerce/e2e-utils';
import { test as setup, expect } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme with templates', async ( { requestUtils } ) => {
	await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

	console.time( 'Database dump time' );
	const cliOutput = await cli(
		'npm run wp-env run tests-cli wp db export blocks_e2e.sql '
	);
	console.log( cliOutput.stdout );
	console.timeEnd( 'Database dump time' );

	expect( cliOutput.stdout ).toContain( 'Success: Exported' );
} );
