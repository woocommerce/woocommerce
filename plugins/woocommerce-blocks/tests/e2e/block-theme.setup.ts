/**
 * External dependencies
 */
import { BLOCK_THEME_SLUG, cli } from '@woocommerce/e2e-utils';
import { test as setup } from '@woocommerce/e2e-playwright-utils';

type ThemeItem = {
	status: string;
	stylesheet: string;
};

setup( 'Sets up the block theme', async ( { requestUtils } ) => {
	const themes = await requestUtils.rest< ThemeItem[] >( {
		path: '/wp/v2/themes',
	} );
	const currentTheme = themes.find( ( { status } ) => status === 'active' );

	if ( ! currentTheme || currentTheme.stylesheet !== BLOCK_THEME_SLUG ) {
		await requestUtils.activateTheme( BLOCK_THEME_SLUG );
		await cli(
			`npm run wp-env run tests-cli -- wp rewrite structure /%postname%/ --hard`
		);
		await cli( `npm run wp-env run tests-cli -- wp rewrite flush --hard` );
	}
} );
