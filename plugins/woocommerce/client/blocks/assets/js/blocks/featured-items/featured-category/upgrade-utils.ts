/**
 * External dependencies
 */
import { createBlock, type BlockInstance } from '@wordpress/blocks';

export const bindCategoryButtonUrl = (
	blocks: BlockInstance[],
	categoryUrl: string,
	state = { hasBoundCategoryButton: false }
): BlockInstance[] =>
	blocks.map( ( block ) => {
		const isCategoryButton =
			! state.hasBoundCategoryButton &&
			block.name === 'core/button' &&
			block.attributes.url === categoryUrl;

		if ( isCategoryButton ) {
			state.hasBoundCategoryButton = true;
		}

		const attributes = isCategoryButton
			? {
					...block.attributes,
					className: [
						block.attributes.className,
						'wc-block-featured-category__link',
					]
						.filter( Boolean )
						.join( ' ' ),
					metadata: {
						...block.attributes.metadata,
						bindings: {
							...block.attributes.metadata?.bindings,
							url: {
								source: 'core/term-data',
								args: { field: 'link' },
							},
						},
					},
			  }
			: block.attributes;

		return createBlock(
			block.name,
			attributes,
			bindCategoryButtonUrl( block.innerBlocks, categoryUrl, state )
		);
	} );
