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
	install?: string;
}

export default function ConnectAccountButton( props: RenewProps ) {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	const url = new URL( wccomSettings?.connectURL ?? '' );
	if ( props.install ) {
		url.searchParams.set( 'install', props.install );
	}
	return (
		<Button href={ url.href } variant={ props.variant ?? 'secondary' }>
			{ __( 'Connect Account', 'woocommerce' ) }
		</Button>
	);
}
