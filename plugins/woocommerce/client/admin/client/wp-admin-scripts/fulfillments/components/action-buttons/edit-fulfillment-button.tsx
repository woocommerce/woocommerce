/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function EditFulfillmentButton( {
	onClick,
}: {
	onClick: () => void;
} ) {
	return (
		<Button variant="secondary" onClick={ onClick } __next40pxDefaultSize>
			{ __( 'Edit fulfillment', 'woocommerce' ) }
		</Button>
	);
}
