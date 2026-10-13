/* Scenario context: Only woocommerce script translations are registered/loaded. woocommerce-extra is not a registered domain and no catalog supplies it. Released heading is translated in the woocommerce catalogs. */
import { __ } from '@wordpress/i18n';
export const getHeading = () => __( 'You may also like…', 'woocommerce' );
