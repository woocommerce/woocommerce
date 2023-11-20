/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { MARKETPLACE_CART_PATH } from '../../../constants';
import { appendURLParams } from '../../../../utils/functions';
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

	function recordTracksEvent() {
		queueRecordEvent( 'marketplace_renew_button_clicked', {
			product_zip_slug: props.subscription.zip_slug,
			product_id: props.subscription.product_id,
		} );
	}

	return (
		<Button
			href={ renewUrl }
			variant={ props.variant ?? 'secondary' }
			onClick={ recordTracksEvent }
		>
			{ __( 'Renew', 'woocommerce' ) }
		</Button>
	);
}
