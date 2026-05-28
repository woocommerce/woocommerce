/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';

export default function EditFulfillmentButton( {
	onClick,
}: {
	onClick: () => void;
} ) {
	const descriptionId = useInstanceId(
		EditFulfillmentButton,
		'edit-fulfillment-description'
	) as string;

	return (
		<>
			<Button
				variant="secondary"
				onClick={ onClick }
				__next40pxDefaultSize
				aria-describedby={ descriptionId }
			>
				{ __( 'Edit fulfillment', 'woocommerce' ) }
			</Button>
			<span id={ descriptionId } className="screen-reader-text">
				{ __(
					'Opens the fulfillment editor to modify fulfillment details',
					'woocommerce'
				) }
			</span>
		</>
	);
}
