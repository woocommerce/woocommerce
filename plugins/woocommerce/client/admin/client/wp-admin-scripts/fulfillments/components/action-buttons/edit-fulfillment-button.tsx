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
		<Button
			variant="secondary"
			onClick={ onClick }
			__next40pxDefaultSize
			aria-label={ __( 'Edit fulfillment details', 'woocommerce' ) }
			aria-describedby="edit-fulfillment-description"
		>
			{ __( 'Edit fulfillment', 'woocommerce' ) }
			<span
				id="edit-fulfillment-description"
				className="screen-reader-text"
			>
				{ __(
					'Opens the fulfillment editor to modify fulfillment details',
					'woocommerce'
				) }
			</span>
		</Button>
	);
}
