/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, useState } from '@wordpress/element';
import { BlockInstance, parse, serialize } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ContentPreview } from '../../components/content-preview';
import { ModalEditor } from '../../components/modal-editor';

/**
 * Internal dependencies
 */

/**
 * By default the blocks variable always contains one paragraph
 * block with empty content, that causes the desciption to never
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

export function Edit() {
	const blockProps = useBlockProps();
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	return (
		<div { ...blockProps }>
			<Button
				variant="secondary"
				onClick={ () => {
					setIsModalOpen( true );
					recordEvent( 'product_add_description_click' );
				} }
			>
				{ description.length
					? __( 'Edit description', 'woocommerce' )
					: __( 'Add description', 'woocommerce' ) }
			</Button>
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
		</div>
	);
}
