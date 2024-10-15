/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { renewUrl } from '../../../../utils/functions';
import { Subscription } from '../../types';

interface RenewProps {
	subscription: Subscription;
	variant?: Button.ButtonVariant;
}

export default function RenewButton( props: RenewProps ) {
	function recordTracksEvent() {
		queueRecordEvent( 'marketplace_renew_button_clicked', {
			product_zip_slug: props.subscription.zip_slug,
			product_id: props.subscription.product_id,
		} );
	}

	return (
		<Button
			href={ renewUrl( props.subscription ) }
			variant={ props.variant ?? 'secondary' }
			onClick={ recordTracksEvent }
		>
			{ __( 'Renew', 'woocommerce' ) }
		</Button>
	);
}
