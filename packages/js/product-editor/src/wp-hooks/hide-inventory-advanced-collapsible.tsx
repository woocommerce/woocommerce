/**
 * External dependencies
 */
import type { BlockInstance } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { evaluate } from '@woocommerce/expression-evaluation';

/**
 * Internal dependencies
 */
import { useEvaluationContext } from '../utils';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const areAllBlocksInvisible = ( blocks: BlockInstance[], context: any ) => {
	return blocks.every( ( block ) => {
		if (
			! block.attributes?._templateBlockHideConditions ||
			! Array.isArray( block.attributes?._templateBlockHideConditions )
		) {
			return false;
		}
		return block.attributes._templateBlockHideConditions.some(
			( condition ) => evaluate( condition.expression, context )
		);
	} );
};

const maybeHideInventoryAdvancedCollapsible = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			const { hasInnerBlocks, allBlocksInvisible: blocksInvisible } =
				useSelect(
					( select ) => {
						// bail early if not the product-inventory-advanced block
						if (
							( props?.attributes as Record< string, string > )
								?._templateBlockId !==
							'product-inventory-advanced'
						) {
							return {
								hasInnerBlocks: true,
								allBlocksInvisible: false,
							};
						}
						const evalContext = useEvaluationContext(
							// eslint-disable-next-line @typescript-eslint/no-explicit-any
							props.context as any
						);
						const advancedCollapsibleBlock = select(
							'core/block-editor'
							// @ts-expect-error Selector is not typed
						).getBlock( props?.clientId as string );

						let allBlocksInvisible = false;
						if ( advancedCollapsibleBlock?.innerBlocks?.length ) {
							const advancedSectionBlock =
								advancedCollapsibleBlock?.innerBlocks[ 0 ];
							allBlocksInvisible = areAllBlocksInvisible(
								advancedSectionBlock?.innerBlocks,
								// @ts-expect-error type is not correct
								evalContext.getEvaluationContext( select )
							);
						}

						return {
							hasInnerBlocks:
								!! advancedCollapsibleBlock?.innerBlocks
									?.length,
							allBlocksInvisible,
						};
					},
					[ props.attributes, props.context, props.clientId ]
				);

			// No inner blocks, so we can render the default block edit.
			if ( ! hasInnerBlocks ) {
				return <BlockEdit { ...props } />;
			}

			if ( blocksInvisible ) {
				return null;
			}

			return <BlockEdit { ...props } />;
		};
	},
	'maybeHideInventoryAdvancedCollapsible'
);

export default function () {
	addFilter(
		'editor.BlockEdit',
		'woocommerce/handle-hide-inventory-advanced-collapsible',
		maybeHideInventoryAdvancedCollapsible
	);
}
