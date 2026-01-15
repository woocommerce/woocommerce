/**
 * External dependencies
 */
import { productsStore } from '@woocommerce/data';
import { useEffect, useMemo } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';

import {
	store as blockEditorStore,
	useBlockProps,
	// @ts-expect-error - useInnerBlocksProps is not exported from @wordpress/block-editor
	useInnerBlocksProps,
	Warning,
	InspectorControls,
} from '@wordpress/block-editor';

/**
 * External dependencies
 */
import { getInnerBlockByName } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { ProductDetailsEditProps } from './types';
import { getTemplate, isAdditionalProductDataEmpty } from './utils';
import { LegacyProductDetailsPreview } from './legacy-preview';
import './editor.scss';

/**
 * Check if block is inside a Query Loop with non-product post type
 *
 * @param {string} clientId The block's client ID
 * @param {string} postType The current post type
 * @return {boolean} Whether the block is in an invalid Query Loop context
 */
const useIsInvalidQueryLoopContext = ( clientId: string, postType: string ) => {
	return useSelect(
		( select ) => {
			const blockParents = select(
				blockEditorStore
			).getBlockParentsByBlockName( clientId, 'core/post-template' );
			return blockParents.length > 0 && postType !== 'product';
		},
		[ clientId, postType ]
	);
};

const Edit = ( {
	clientId,
	context,
	attributes,
	setAttributes,
}: ProductDetailsEditProps ) => {
	const blockProps = useBlockProps();
	const { hideTabTitle } = attributes;

	const product = useSelect(
		( select ) => {
			if ( ! context.postId ) {
				return null;
			}
			const { getProduct } = select( productsStore );
			return getProduct( Number( context.postId ) );
		},
		[ context.postId ]
	);

	const {
		isInnerBlockOfSingleProductBlock,
		hasInnerBlocks,
		wasBlockJustInserted,
		accordionItemClientId,
	} = useSelect(
		( select ) => {
			const blockEditorSelect = select( blockEditorStore );

			// Check if block is inner block of single product block
			const singleProductParentBlocks = blockEditorSelect
				// @ts-expect-error - getBlockParentsByBlockName is not typed
				.getBlockParentsByBlockName(
					clientId,
					'woocommerce/single-product'
				);
			const isInnerBlock = singleProductParentBlocks.length > 0;

			// Get inner blocks and insertion status
			// @ts-expect-error - getBlocks is not typed
			const blocks = blockEditorSelect.getBlocks( clientId );
			const innerBlocks = blocks.length > 0;
			const blockJustInserted =
				// @ts-expect-error - wasBlockJustInserted is not typed
				blockEditorSelect.wasBlockJustInserted( clientId );

			const productDetailsBlock = select(
				blockEditorStore
				// @ts-expect-error - getBlocksByName is not typed
			).getBlock( clientId );

			const productSpecificationClientId = getInnerBlockByName(
				productDetailsBlock,
				'woocommerce/product-specifications'
			)?.clientId;

			const accordionClientId = select(
				blockEditorStore
				// @ts-expect-error - getBlockParentsByBlockName is not typed
			).getBlockParentsByBlockName(
				productSpecificationClientId ?? '',
				'woocommerce/accordion-item'
			)[ 0 ];

			return {
				isInnerBlockOfSingleProductBlock: isInnerBlock,
				hasInnerBlocks: innerBlocks,
				wasBlockJustInserted: blockJustInserted,
				accordionItemClientId: accordionClientId,
			};
		},
		[ clientId ]
	);

	const template = useMemo( () => {
		return getTemplate( product, {
			isInnerBlockOfSingleProductBlock,
		} );
	}, [ product, isInnerBlockOfSingleProductBlock ] );

	const { removeBlock } = useDispatch( blockEditorStore );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: wasBlockJustInserted ? template : undefined,
	} );

	/**
	 * In some cases, the template variable is calculated before all the props are set.
	 * This is why we need to do this additional check.
	 * Check the PR for more details: https://github.com/woocommerce/woocommerce/pull/59686
	 */
	useEffect( () => {
		if (
			wasBlockJustInserted &&
			product &&
			isAdditionalProductDataEmpty( product ) &&
			accordionItemClientId
		) {
			removeBlock( accordionItemClientId );
		}
	}, [ wasBlockJustInserted, accordionItemClientId, product, removeBlock ] );

	const isInvalidQueryLoopContext = useIsInvalidQueryLoopContext(
		clientId,
		context.postType
	);
	if ( isInvalidQueryLoopContext ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'The Product Details block requires a product context. When used in a Query Loop, the Query Loop must be configured to display products.',
						'woocommerce'
					) }
				</Warning>
			</div>
		);
	}

	if ( hasInnerBlocks || wasBlockJustInserted ) {
		return <div { ...innerBlocksProps } />;
	}

	return (
		<div { ...blockProps }>
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __(
							'Show tab title in content',
							'woocommerce'
						) }
						checked={ ! hideTabTitle }
						onChange={ () =>
							setAttributes( {
								hideTabTitle: ! hideTabTitle,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<LegacyProductDetailsPreview hideTabTitle={ hideTabTitle } />
			</Disabled>
		</div>
	);
};

export default Edit;
