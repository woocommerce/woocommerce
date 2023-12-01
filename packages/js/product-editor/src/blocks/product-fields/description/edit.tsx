/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import {
	BlockAttributes,
	BlockInstance,
	parse,
	serialize,
} from '@wordpress/blocks';
import { useInnerBlocksProps } from '@wordpress/block-editor';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ContentPreview } from '../../../components/content-preview';
import { ModalEditor } from '../../../components/modal-editor';
import { ProductEditorBlockEditProps } from '../../../types';
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';

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
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

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

			{ isModalOpen && (
				<ModalEditor
					initialBlocks={ parse( description ) }
					onChange={ ( blocks ) => {
						const html = serialize(
							clearDescriptionIfEmpty( blocks )
						);
						setDescription( html );
					} }
					onClose={ () => setIsModalOpen( false ) }
					title={ __( 'Edit description', 'woocommerce' ) }
				/>
			) }
			{ !! description.length && (
				<ContentPreview content={ description } />
			) }
			{ isModalOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
