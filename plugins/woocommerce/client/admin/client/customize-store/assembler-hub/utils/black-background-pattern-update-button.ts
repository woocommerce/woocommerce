/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

const updateFeaturedCategoryCoverImagePattern = (
	featuredCategoryCoverImagePatternParentBlocks: BlockInstance[],
	callback: ( buttonBlocks: BlockInstance[] ) => void
) => {
	const coverBlocks = featuredCategoryCoverImagePatternParentBlocks.map(
		( featuredCategoryCoverImagePatternParentBlock ) =>
			featuredCategoryCoverImagePatternParentBlock.innerBlocks.find(
				( block ) => block.name === 'core/cover'
			)
	);

	const parentButtonBlocks = coverBlocks.map( ( coverBlock ) =>
		coverBlock?.innerBlocks.find(
			( block ) => block.name === 'core/buttons'
		)
	);

	const buttonBlocks = parentButtonBlocks.map(
		( parentButtonBlock ) => parentButtonBlock?.innerBlocks[ 0 ]
	);

	callback( buttonBlocks as BlockInstance[] );
};

const updateJustArrivedFullHeroPattern = (
	justArrivedFullHeroPatterns: BlockInstance[],
	callback: ( buttonBlocks: BlockInstance[] ) => void
) => {
	const parentButtonBlocks = justArrivedFullHeroPatterns.map(
		( justArrivedFullHeroPattern ) =>
			justArrivedFullHeroPattern?.innerBlocks[ 0 ].innerBlocks.find(
				( block ) => block.name === 'core/buttons'
			)
	);

	const buttonBlocks = parentButtonBlocks
		.map( ( parentButtonBlock ) => parentButtonBlock?.innerBlocks[ 0 ] )
		.filter( Boolean );

	if ( ! buttonBlocks ) {
		return;
	}

	callback( buttonBlocks as BlockInstance[] );
};

export const isJustArrivedFullHeroPattern = ( block: BlockInstance ) =>
	block.name === 'core/cover' &&
	block.attributes.url.includes(
		'music-black-and-white-white-photography.jpg'
	);

export const isFeaturedCategoryCoverImagePattern = ( block: BlockInstance ) =>
	block.attributes?.metadata?.name === 'Featured Category Cover Image';

/**
 * This is temporary solution to change the button color on the cover block when the color palette is New - Neutral.
 * The real fix should be done on Gutenberg side: https://github.com/WordPress/gutenberg/issues/58004
 *
 */
export const findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate =
	(
		blocks: BlockInstance[],
		callback: ( buttonBlocks: BlockInstance[] ) => void
	) => {
		const justArrivedFullHeroPatterns = blocks.filter(
			isJustArrivedFullHeroPattern
		);

		if ( justArrivedFullHeroPatterns ) {
			updateJustArrivedFullHeroPattern(
				justArrivedFullHeroPatterns,
				callback
			);
		}

		const featuredCategoryCoverImagePatterns = blocks.filter(
			isFeaturedCategoryCoverImagePattern
		);

		if ( featuredCategoryCoverImagePatterns ) {
			updateFeaturedCategoryCoverImagePattern(
				featuredCategoryCoverImagePatterns,
				callback
			);
		}
		return blocks;
	};

export const PRODUCT_HERO_PATTERN_BUTTON_STYLE = {
	color: { background: '#ffffff', text: '#000000' },
};
