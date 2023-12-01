/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useMemo } from '@wordpress/element';
import { BlockInstance, parse, serialize } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useEntityProp } from '@wordpress/core-data';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import { ContentPreview } from '../../../components/content-preview';
import { ModalEditor } from '../../../components/modal-editor';
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';
import { store as productEditorUiStore } from '../../../store/product-editor-ai';
import type { DescriptionBlockEditComponent } from './types';

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
}: DescriptionBlockEditComponent ) {
	const blockProps = useWooBlockProps( attributes );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	// Check if the Modal editor is open from the store.
	const isModalEditorOpen = useSelect( ( select ) => {
		return select( productEditorUiStore ).isModalEditorOpen();
	}, [] );

	const { closeModalEditor } = useDispatch( productEditorUiStore );

	const parsedBlocks = useMemo( () => {
		try {
			return parse( description );
		} catch ( e ) {
			return [];
		}
	}, [ description ] );

	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			templateLock: 'contentOnly',
			allowedBlocks: [ 'woocommerce/product-summary-field' ],
		}
	);

	return (
		<div { ...blockProps }>
			<div { ...innerBlockProps } />

			{ isModalEditorOpen && (
				<ModalEditor
					initialBlocks={ parsedBlocks }
					onChange={ ( blocks ) => {
						const html = serialize(
							clearDescriptionIfEmpty( blocks )
						);
						setDescription( html );
					} }
					onClose={ closeModalEditor }
					title={ __( 'Edit description', 'woocommerce' ) }
				/>
			) }

			{ !! description.length && (
				<ContentPreview content={ description } />
			) }

			{ isModalEditorOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
