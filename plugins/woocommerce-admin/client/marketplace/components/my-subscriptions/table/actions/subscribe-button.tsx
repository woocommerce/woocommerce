/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { subscribeUrl } from '../../../../utils/functions';
import { Subscription } from '../../types';

interface SubscribeProps {
	subscription: Subscription;
	variant?: Button.ButtonVariant;
}

export default function SubscribeButton( props: SubscribeProps ) {
	return (
		<Button
			href={ subscribeUrl( props.subscription ) }
			variant={ props.variant ?? 'secondary' }
		>
			{ __( 'Subscribe', 'woocommerce' ) }
		</Button>
	);
}
