/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MARKETPLACE_CART_PATH } from '~/marketplace/components/constants';
import { appendURLParams } from '~/marketplace/utils/functions';
import { Subscription } from '../../types';

interface RenewProps {
	subscription: Subscription;
	variant?: Button.ButtonVariant;
}

export default function RenewButton( props: RenewProps ) {
	const renewUrl = appendURLParams( MARKETPLACE_CART_PATH, [
		[ 'renew_product', props.subscription.product_id.toString() ],
		[ 'product_key', props.subscription.product_key ],
		[ 'order_id', props.subscription.order_id.toString() ],
	] );

	return (
		<Button href={ renewUrl } variant={ props.variant ?? 'secondary' }>
			{ __( 'Renew', 'woocommerce' ) }
		</Button>
	);
}
