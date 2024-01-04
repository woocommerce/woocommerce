/**
 * External dependencies
 */
import type { BlockInstance } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { select } from '@wordpress/data';
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

const maybeHideInventoryAdvancedCollapsible = createHigherOrderComponent<
	Record< string, unknown >
>( ( BlockEdit ) => {
	return ( props ) => {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const evalContext = useEvaluationContext( props.context as any );

		if (
			( props?.attributes as Record< string, string > )
				?._templateBlockId !== 'product-inventory-advanced'
		) {
			return <BlockEdit { ...props } />;
		}

		// get the inventory section block instance
		const advancedCollapsibleBlock = select( 'core/block-editor' ).getBlock(
			props?.clientId as string
		);

		// No inner blocks, so we can render the default block edit.
		if ( ! advancedCollapsibleBlock?.innerBlocks?.length ) {
			return <BlockEdit { ...props } />;
		}

		const advancedSectionBlock = advancedCollapsibleBlock?.innerBlocks[ 0 ];

		if (
			areAllBlocksInvisible(
				advancedSectionBlock?.innerBlocks,
				evalContext.getEvaluationContext( select )
			)
		) {
			return null;
		}

		return <BlockEdit { ...props } />;
	};
}, 'maybeHideInventoryAdvancedCollapsible' );

export default function () {
	addFilter(
		'editor.BlockEdit',
		'woocommerce/handle-hide-inventory-advanced-collapsible',
		maybeHideInventoryAdvancedCollapsible
	);
}
