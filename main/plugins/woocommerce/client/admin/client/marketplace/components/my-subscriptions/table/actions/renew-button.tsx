/**
 * External dependencies
 */
import { ComponentProps } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { queueRecordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { renewUrl } from '../../../../utils/functions';
import { Subscription } from '../../types';

type ButtonProps = ComponentProps< typeof Button >;

interface RenewProps {
	subscription: Subscription;
	variant?: ButtonProps[ 'variant' ];
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
