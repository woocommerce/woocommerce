/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '../../../../../utils/admin-settings';

interface RenewProps {
	variant?: Button.ButtonVariant;
}

export default function ConnectAccountButton( props: RenewProps ) {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	return (
		<Button
			href={ wccomSettings?.connectURL }
			variant={ props.variant ?? 'secondary' }
		>
			{ __( 'Connect account', 'woocommerce' ) }
		</Button>
	);
}
