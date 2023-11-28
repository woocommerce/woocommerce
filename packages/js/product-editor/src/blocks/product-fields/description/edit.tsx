/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import {
	BlockAttributes,
	BlockInstance,
	createBlock,
	parse,
	serialize,
} from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useInnerBlocksProps } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
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
	clientId,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ serializeDescriptionBlocks, setSerializeDescriptionBlocks ] =
		useEntityProp< string >( 'postType', 'product', 'description' );

	const parsedBlocks = useMemo( () => {
		try {
			return parse( serializeDescriptionBlocks );
		} catch ( e ) {
			return [];
		}
	}, [ serializeDescriptionBlocks ] );

	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const innerBlockProps = useInnerBlocksProps();

	/*
	 * Pick the description blocks,
	 * based on the current block client ID.
	 */
	const descriptionBlocks = useSelect(
		( select ) =>
			select( 'core/block-editor' ).getBlock( clientId )?.innerBlocks ||
			[],
		[ clientId ]
	);

	/*
	 * Always ensure the description block is not empty (kinda hack)
	 * It will create a paragraph block with a placeholder if the
	 * description blocks are empty.
	 */
	useEffect( () => {
		// When description blocks are not empty -> bail early
		if ( descriptionBlocks?.length ) {
			return;
		}

		replaceInnerBlocks(
			clientId,
			[
				createBlock( 'core/paragraph', {
					placeholder: __(
						'Add a description for this product.',
						'woocommerce'
					),
				} ),
			],
			false
		);
	}, [ clientId, descriptionBlocks, replaceInnerBlocks ] );

	/*
	 * Populate the description blocks
	 * when the parsed blocks are not empty,
	 * in the first render.
	 */
	useEffect( () => {
		if ( ! parsedBlocks.length ) {
			return;
		}

		replaceInnerBlocks( clientId, parsedBlocks, false );
	}, [ clientId, replaceInnerBlocks ] ); // eslint-disable-line

	return (
		<div { ...blockProps }>
			<div { ...innerBlockProps } />

			<Button
				variant="secondary"
				onClick={ () => {
					setIsModalOpen( true );
					recordEvent( 'product_add_description_click' );
				} }
			>
				{ serializeDescriptionBlocks.length
					? __( 'Edit description', 'woocommerce' )
					: __( 'Add description', 'woocommerce' ) }
			</Button>

			{ isModalOpen && (
				<ModalEditor
					initialBlocks={ parsedBlocks }
					onChange={ ( blocks ) => {
						replaceInnerBlocks( clientId, blocks, false );

						const html = serialize(
							clearDescriptionIfEmpty( blocks )
						);
						setSerializeDescriptionBlocks( html );
					} }
					onClose={ () => setIsModalOpen( false ) }
					title={ __( 'Edit description', 'woocommerce' ) }
				/>
			) }
			{ isModalOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
