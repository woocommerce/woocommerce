/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createElement,
	useEffect,
	useMemo,
	useState,
	Fragment,
} from '@wordpress/element';
import {
	BlockAttributes,
	createBlock,
	parse,
	serialize,
} from '@wordpress/blocks';
import { ToolbarButton } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { BlockControls, useInnerBlocksProps } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import { ModalEditor } from '../../../components/modal-editor';
import { ProductEditorBlockEditProps } from '../../../types';
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';

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

	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			template: [
				'core/paragraph',
				{
					placeholder: __(
						'Add a description for this product. Type / to add a block that may contain text, images, or video.',
						'woocommerce'
					),
				},
			],
			templateLock: false,
			allowedBlocks: [
				'core/paragraph',
				'core/heading',
				'core/list',
				'core/image',
				'core/video',
				'core/cover',
				'core/columns',
				'core/media-text',
				'jetpack/videopress',
			],
		}
	);

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
	 * Watch the description blocks,
	 * to update the description HTML when the blocks change,
	 * but also ensuring to replace the description blocks
	 * when the there aren't any blocks.
	 */
	useEffect( () => {
		// When description blocks are not empty -> bail early
		if ( descriptionBlocks?.length ) {
			const html = serialize( descriptionBlocks );
			return setSerializeDescriptionBlocks( html );
		}

		/*
		 * Is the user remove all the blocks,
		 * ensure to replace them
		 * with a default paragraph block.
		 */
		replaceInnerBlocks(
			clientId,
			[
				createBlock( 'core/paragraph', {
					placeholder: __(
						'Add a description for this product. Type / to chose a block',
						'woocommerce'
					),
				} ),
			],
			false
		);
	}, [
		clientId,
		descriptionBlocks,
		replaceInnerBlocks,
		setSerializeDescriptionBlocks,
	] );

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
			<BlockControls group="other">
				<ToolbarButton
					label={ __( 'Edit Product description', 'woocommerce' ) }
					onClick={ () => {
						setIsModalOpen( true );
						recordEvent( 'product_add_description_click' );
					} }
				>
					{ __( 'Full editor', 'woocommerce' ) }
				</ToolbarButton>
			</BlockControls>

			<div { ...innerBlockProps } />

			{ isModalOpen && (
				<ModalEditor
					initialBlocks={ parsedBlocks }
					onChange={ ( blocks ) => {
						// Replace description blocks
						replaceInnerBlocks( clientId, blocks, false );
					} }
					onClose={ () => setIsModalOpen( false ) }
					title={ __( 'Edit description', 'woocommerce' ) }
				/>
			) }

			{ isModalOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
