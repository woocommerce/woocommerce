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

interface SubscribeProps {
	subscription: Subscription;
	variant?: Button.ButtonVariant;
}

export default function SubscribeButton( props: SubscribeProps ) {
	const subscribeUrl = appendURLParams( MARKETPLACE_CART_PATH, [
		[ 'add-to-cart', props.subscription.product_id.toString() ],
	] );
	return (
		<Button href={ subscribeUrl } variant={ props.variant ?? 'secondary' }>
			{ __( 'Subscribe', 'woocommerce' ) }
		</Button>
	);
}
