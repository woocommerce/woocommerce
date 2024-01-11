/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ButtonProps } from '@wordpress/components/build-types/button/types';

/**
 * Internal dependencies
 */
import { connectUrl } from '../../../../utils/functions';

interface RenewProps {
	variant?: ButtonProps[ 'variant' ];
	install?: string;
}

export default function ConnectAccountButton( props: RenewProps ) {
	const url = new URL( connectUrl() );
	if ( props.install ) {
		url.searchParams.set( 'install', props.install );
	}
	return (
		<Button href={ url.href } variant={ props.variant ?? 'secondary' }>
			{ __( 'Connect Account', 'woocommerce' ) }
		</Button>
	);
}
