/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
	return (
		<Button
			href={ renewUrl( props.subscription ) }
			variant={ props.variant ?? 'secondary' }
		>
			{ __( 'Renew', 'woocommerce' ) }
		</Button>
	);
}
