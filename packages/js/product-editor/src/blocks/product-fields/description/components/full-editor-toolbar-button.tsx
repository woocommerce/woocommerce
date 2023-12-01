/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../../../../store/product-editor-ai';

export default function FullEditorToolbarButton( {
	label = __( 'Edit Product description', 'woocommerce' ),
	text = __( 'Full editor', 'woocommerce' ),
} ) {
	const { openModalEditor } = dispatch( store );

	return (
		<ToolbarButton
			label={ label }
			onClick={ () => {
				recordEvent( 'product_add_description_click' );
				openModalEditor();
			} }
		>
			{ text }
		</ToolbarButton>
	);
}
