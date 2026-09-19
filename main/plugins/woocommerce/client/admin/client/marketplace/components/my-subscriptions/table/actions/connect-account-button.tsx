/**
 * External dependencies
 */
import { ComponentProps } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { connectUrl } from '../../../../utils/functions';

type ButtonProps = ComponentProps< typeof Button >;

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
