/**
 * External dependencies
 */
import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Internal dependencies
 */
import { MARKETPLACE_SUBSCRIPTIONS_PATH } from "~/marketplace/components/constants";

export default function RenewButton() {
	return (
		<Button href={ MARKETPLACE_SUBSCRIPTIONS_PATH } variant="primary" >
			{ __( 'Renew', 'woocommerce' ) }
		</Button>
	)
}