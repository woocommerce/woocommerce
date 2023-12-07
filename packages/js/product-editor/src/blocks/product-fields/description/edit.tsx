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
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ContentPreview } from '../../../components/content-preview';
import { ProductEditorBlockEditProps } from '../../../types';
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';
import { store } from '../../../store/product-editor-ui';

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
	const { isModalEditorOpen, modalEditorBlocks, hasChanged } = useSelect(
		( select ) => {
			return {
				isModalEditorOpen: select( store ).isModalEditorOpen(),
				modalEditorBlocks: select( store ).getModalEditorBlocks(),
				hasChanged: select( store ).getModalEditorContentHasChanged(),
			};
		},
		[]
	);

	const { openModalEditor, setModalEditorBlocks } = useDispatch( store );

	// Update the description when the blocks change.
	useEffect( () => {
		if ( ! hasChanged ) {
			return;
		}

		if ( ! modalEditorBlocks?.length ) {
			setDescription( '' );
		}

		const html = serialize( clearDescriptionIfEmpty( modalEditorBlocks ) );
		setDescription( html );
	}, [ modalEditorBlocks, setDescription, hasChanged ] );

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

			<Button
				variant="secondary"
				onClick={ () => {
					if ( description ) {
						setModalEditorBlocks( parse( description ) );
					}

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
