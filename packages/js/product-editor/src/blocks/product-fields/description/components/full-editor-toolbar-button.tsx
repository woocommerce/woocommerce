/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { dispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { parse, rawHandler } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { store } from '../../../../store/product-editor-ui';
import { getContentFromFreeform } from '../edit';

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
				let parsedBlocks = parse( description );
				const freeformContent = getContentFromFreeform( parsedBlocks );

				// replace the freeform block with a paragraph block
				if ( freeformContent ) {
					parsedBlocks = rawHandler( { HTML: freeformContent } );
				}

				setModalEditorBlocks( parsedBlocks );
				recordEvent( 'product_add_description_click' );
				openModalEditor();
			} }
		>
			{ text }
		</ToolbarButton>
	);
}
