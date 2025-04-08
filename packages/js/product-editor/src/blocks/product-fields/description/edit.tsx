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
import type { DescriptionBlockEditComponent } from './types';
import FullEditorToolbarButton from './components/full-editor-toolbar-button';
import { wooProductEditorUiStore } from '../../../store/product-editor-ui';

/**
 * Check whether the parsed blocks become from the summary block.
 *
 * @param {BlockInstance[]} blocks - The block list
 * @return {string|false} The content of the freeform block if it's a freeform block, false otherwise.
 */
export function getContentFromFreeform(
	blocks: BlockInstance[]
): false | string {
	// Check whether the parsed blocks become from the summary block:
	const isCoreFreeformBlock =
		blocks.length === 1 && blocks[ 0 ].name === 'core/freeform';

	if ( isCoreFreeformBlock ) {
		return blocks[ 0 ].attributes.content;
	}

	return false;
}

export function DescriptionBlockEdit( {
	attributes,
}: DescriptionBlockEditComponent ) {
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		'description'
	);

	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( [] );

	// Pick Modal editor data from the store.
	const { isModalEditorOpen, modalEditorBlocks, hasChanged } = useSelect(
		( select ) => {
			return {
				isModalEditorOpen: select(
					wooProductEditorUiStore
				).isModalEditorOpen(),
				modalEditorBlocks: select(
					wooProductEditorUiStore
				).getModalEditorBlocks(),
				hasChanged: select(
					wooProductEditorUiStore
				).getModalEditorContentHasChanged(),
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

		/*
		 * First quick check to avoid parsing process,
		 * since it's an expensive operation.
		 */
		if ( description.indexOf( '<!-- wp:' ) === -1 ) {
			return;
		}

		const parsedBlocks = parse( description );
		// Check whether the parsed blocks become from the summary block:
		if ( getContentFromFreeform( parsedBlocks ) ) {
			return;
		}

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

		const html = serialize( modalEditorBlocks );
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
