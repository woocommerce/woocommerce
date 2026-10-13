/* Scenario context: PR explicitly fixes the shipped English typo "liek" to "like" in cross-sells heading. The translation identity change is called out as part of this typo correction. Domain and loading are unchanged. */
import { __ } from '@wordpress/i18n';
export const getHeading = () => __( 'You may also like…', 'woocommerce' );
