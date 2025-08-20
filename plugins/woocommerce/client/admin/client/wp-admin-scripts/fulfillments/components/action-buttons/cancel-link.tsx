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
			aria-label={ __(
				'Cancel current action and return to previous state',
				'woocommerce'
			) }
			aria-describedby="cancel-link-description"
		>
			{ __( 'Cancel', 'woocommerce' ) }
			<span id="cancel-link-description" className="screen-reader-text">
				{ __(
					'Cancels the current operation without saving changes',
					'woocommerce'
				) }
			</span>
		</Button>
	);
}
