/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

export default function CancelLink( { onClick }: { onClick: () => void } ) {
	return (
		<Button
			variant="link"
			onClick={ onClick }
			style={ { flex: 1 } }
			__next40pxDefaultSize
		>
			{ __( 'Cancel', 'woocommerce' ) }
		</Button>
	);
}
