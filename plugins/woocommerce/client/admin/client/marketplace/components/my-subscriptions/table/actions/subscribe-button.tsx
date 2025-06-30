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
import { subscribeUrl } from '../../../../utils/functions';
import { Subscription } from '../../types';

type ButtonProps = ComponentProps< typeof Button >;

interface SubscribeProps {
	subscription: Subscription;
	variant?: ButtonProps[ 'variant' ];
}

export default function SubscribeButton( props: SubscribeProps ) {
	function recordTracksEvent() {
		queueRecordEvent( 'marketplace_subscribe_button_clicked', {
			product_zip_slug: props.subscription.zip_slug,
			product_id: props.subscription.product_id,
		} );
	}

	return (
		<Button
			href={ subscribeUrl( props.subscription ) }
			variant={ props.variant ?? 'secondary' }
			onClick={ recordTracksEvent }
		>
			{ __( 'Subscribe', 'woocommerce' ) }
		</Button>
	);
}
