/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { enableAutorenewalUrl } from '../../../../utils/functions';
import { Subscription } from '../../types';

interface AutoRenewProps {
	subscription: Subscription;
	variant?: Button.ButtonVariant;
}

export default function AutoRenewButton( props: AutoRenewProps ) {
	function recordTracksEvent() {
		queueRecordEvent( 'marketplace_auto_renew_button_clicked', {
			order_id: props.subscription.order_id,
			product_id: props.subscription.product_id,
		} );
	}

	return (
		<Button
			href={ enableAutorenewalUrl( props.subscription ) }
			variant={ props.variant ?? 'secondary' }
			onClick={ recordTracksEvent }
		>
			{ __( 'Renew', 'woocommerce' ) }
		</Button>
	);
}
