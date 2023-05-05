/**
 * External dependencies
 */
import { BLOCK_THEME_SLUG, cli } from '@woocommerce/e2e-utils';

cli( `npm run wp-env run tests-cli "wp theme activate ${ BLOCK_THEME_SLUG }` );
