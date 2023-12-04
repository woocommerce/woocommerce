/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
export default function FullEditorToolbarButton( {
	label = __( 'Edit Product description', 'woocommerce' ),
	text = __( 'Full editor', 'woocommerce' ),
} ) {
	return (
		<ToolbarButton
			label={ label }
			onClick={ () => {
				recordEvent( 'product_add_description_click' );
				// @todo: dispatch acion once store is ready to use
				// @see https://github.com/woocommerce/woocommerce/pull/41859
			} }
		>
			{ text }
		</ToolbarButton>
	);
}
