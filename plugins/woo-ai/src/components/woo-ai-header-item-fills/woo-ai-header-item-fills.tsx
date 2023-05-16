/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WooAIButtonItem } from '../index';

registerPlugin( 'woocommerce-ai-product-description-header', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-ai-product-description',
	render: () => (
		<>
			<WooAIButtonItem>
				<button
					className="button wp-media-button wc-write-it-for-me"
					type="button"
				>
					{ __( 'Write it for me (beta)', 'woocommerce' ) }
				</button>
			</WooAIButtonItem>
		</>
	),
} );
