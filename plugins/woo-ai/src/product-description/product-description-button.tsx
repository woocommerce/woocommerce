/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export function ProductDescriptionButton() {
	return (
		<button
			className="button wp-media-button wc-write-it-for-me"
			type="button"
		>
			{ __( 'Write it for me (beta)', 'woocommerce' ) }
		</button>
	);
}
