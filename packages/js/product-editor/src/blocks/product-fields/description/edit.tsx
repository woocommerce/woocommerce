/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useState,
	Fragment,
} from '@wordpress/element';
import { BlockInstance, parse, serialize } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import classNames from 'classnames';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
	// @ts-expect-error no exported member.
	BlockPreview,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';
import { store } from '../../../store/product-editor-ui';
import type { DescriptionBlockEditComponent } from './types';
import FullEditorToolbarButton from './components/full-editor-toolbar-button';

/**
 * Internal dependencies
 */

/**
 * By default the blocks variable always contains one paragraph
 * block with empty content, that causes the description to never
 * be empty. This function removes the default block to keep
 * the description empty.
 *
 * todo: this is not optimal. We cannot rely on the content attribute to
 * determine whether the description is empty or not
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
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	const [ descriptionBlocks, setDescriptionBlocks ] = useState( [] );

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

	// Parse the description into blocks.
	useEffect( () => {
		if ( ! description ) {
			setDescriptionBlocks( [] );
			return;
		}

		const parsedBlocks = parse( description );
		// Check whether the parsed blocks become from the summary block:
		const isSummaryBlock =
			parsedBlocks.length === 1 &&
			parsedBlocks[ 0 ].name === 'core/freeform';

		if ( isSummaryBlock ) {
			return;
		}

		// console.log( 'parsedBlocks: ', parsedBlocks );
		setDescriptionBlocks( parsedBlocks );
	}, [ description ] );

	/*
	 * From Modal Editor -> Description entity.
	 * Update the description when the modal editor blocks change.
	 */
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

	const blockProps = useWooBlockProps( attributes, {
		className: classNames( { 'has-blocks': !! description.length } ),
		tabIndex: 0,
	} );

	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			templateLock: 'contentOnly',
			allowedBlocks: [ 'woocommerce/product-summary-field' ],
		}
	);

	return (
		<div { ...blockProps }>
			{ !! descriptionBlocks?.length ? (
				<>
					<BlockControls>
						<FullEditorToolbarButton
							text={ __( 'Edit in full editor', 'woocommerce' ) }
						/>
					</BlockControls>

					<BlockPreview
						blocks={ descriptionBlocks }
						viewportWidth={ 800 }
						additionalStyles={ [
							{ css: 'body { padding: 32px; height: 10000px }' }, // hack: setting height to 10000px to ensure the preview is not cut off.
						] }
					/>
				</>
			) : (
				<div { ...innerBlockProps } />
			) }

			{ isModalEditorOpen && <ModalEditorWelcomeGuide /> }
		</div>
	);
}
