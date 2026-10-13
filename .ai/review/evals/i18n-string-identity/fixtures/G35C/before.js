/* Scenario context: The empty-state label is new UI first introduced in this release and has no prior msgid. Existing heading and translations are unchanged. New label uses the same registered woocommerce domain. */
import { __ } from '@wordpress/i18n';
export const getHeading = () => __( 'You may also like…', 'woocommerce' );
