/* Scenario context: Copy-only change to a released cross-sells heading. This is not a typo fix. Deployed locale catalogs contain translations for "You may also like…" but not "You might also like…". Script textdomain remains woocommerce and catalog loading is unchanged. */
import { __ } from '@wordpress/i18n';
export const getHeading = () => __( 'You may also like…', 'woocommerce' );
