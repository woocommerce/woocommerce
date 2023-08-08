/**
 * External dependencies
 */
import { CLASSIC_THEME_SLUG, cli } from '@woocommerce/e2e-utils';

cli(
	`npm run wp-env run tests-cli "wp theme activate ${ CLASSIC_THEME_SLUG }`
);
