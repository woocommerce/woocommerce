/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useEffect } from '@wordpress/element';
import {
	BlockAttributes,
	BlockInstance,
	parse,
	serialize,
} from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ContentPreview } from '../../../components/content-preview';
import { ProductEditorBlockEditProps } from '../../../types';
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';
import { store as productEditorUiStore } from '../../../store/product-editor-ui';

/**
 * Internal dependencies
 */

/**
 * By default the blocks variable always contains one paragraph
 * block with empty content, that causes the description to never
 * be empty. This function removes the default block to keep
 * the description empty.
 *
 * @param blocks The block list
 * @return Empty array if there is only one block with empty content
 * in the list. The same block list otherwise.
 */
function clearDescriptionIfEmpty( blocks: BlockInstance[] ) {
	if ( blocks.length === 1 ) {
		const { content } = blocks[ 0 ].attributes;
		if ( ! content || ! content.trim() ) {
			return [];
		}
	}
	return blocks;
}

export function DescriptionBlockEdit( {
	attributes,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	// Pick Modal editor data from the store.
	const { isModalEditorOpen, blocks } = useSelect( ( select ) => {
		return {
			isModalEditorOpen:
				select( productEditorUiStore ).isModalEditorOpen(),
			blocks: select( productEditorUiStore ).getModalEditorBlocks(),
		};
	}, [] );

	const { openModalEditor, setModalEditorBlocks } =
		useDispatch( productEditorUiStore );

	/*
	 * Populate the modal editor with the description blocks,
	 * in the first render only if the description is not empty.
	 */
	useEffect( () => {
		if ( ! description ) {
			return;
		}

		setModalEditorBlocks( parse( description ) );
	}, [] ); // eslint-disable-line

	// Update the description when the blocks change.
	useEffect( () => {
		if ( ! blocks?.length ) {
			setDescription( '' );
		}

		const html = serialize( clearDescriptionIfEmpty( blocks ) );

		setDescription( html );
	}, [ blocks, setDescription ] );

	return (
		<div { ...blockProps }>
			<Button
				variant="secondary"
				onClick={ () => {
					openModalEditor();
					recordEvent( 'product_add_description_click' );
				} }
			>
				{ description.length
					? __( 'Edit description', 'woocommerce' )
					: __( 'Add description', 'woocommerce' ) }
			</Button>

			{ !! description.length && (
				<ContentPreview content={ description } />
			) }

			{ isModalEditorOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
