/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */

export default function CancelLink( { onClick }: { onClick: () => void } ) {
	const descriptionId = useInstanceId(
		CancelLink,
		'cancel-link-description'
	) as string;

	return (
		<>
			<Button
				variant="link"
				onClick={ onClick }
				style={ { flex: 1 } }
				__next40pxDefaultSize
				aria-describedby={ descriptionId }
			>
				{ __( 'Cancel', 'woocommerce' ) }
			</Button>
			<span id={ descriptionId } className="screen-reader-text">
				{ __(
					'Cancels the current operation without saving changes',
					'woocommerce'
				) }
			</span>
		</>
	);
}
