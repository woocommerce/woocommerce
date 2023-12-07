/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { parse } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { store } from '../../../../store/product-editor-ui';

export default function FullEditorToolbarButton( {
	label = __( 'Edit Product description', 'woocommerce' ),
	text = __( 'Full editor', 'woocommerce' ),
} ) {
	const { openModalEditor, setModalEditorBlocks } = dispatch( store );
	const [ description ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	return (
		<ToolbarButton
			label={ label }
			onClick={ () => {
				setModalEditorBlocks( parse( description ) );
				recordEvent( 'product_add_description_click' );
				openModalEditor();
			} }
		>
			{ text }
		</ToolbarButton>
	);
}
