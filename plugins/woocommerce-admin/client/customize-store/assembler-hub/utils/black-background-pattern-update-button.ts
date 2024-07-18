/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

const updateFeaturedCategoryCoverImagePattern = (
	featuredCategoryCoverImagePatternParentBlock: BlockInstance,
	callback: ( buttonBlock: BlockInstance ) => void
) => {
	const coverBlock =
		featuredCategoryCoverImagePatternParentBlock.innerBlocks.find(
			( block ) => block.name === 'core/cover'
		);

	const buttonsBlock = coverBlock?.innerBlocks.find(
		( block ) => block.name === 'core/buttons'
	);

	const buttonBlock = buttonsBlock?.innerBlocks[ 0 ];

	if ( ! buttonBlock ) {
		return;
	}

	callback( buttonBlock );
};

const updateJustArrivedFullHeroPattern = (
	justArrivedFullHeroPattern: BlockInstance,
	callback: ( buttonBlock: BlockInstance ) => void
) => {
	const buttonsBlock =
		justArrivedFullHeroPattern?.innerBlocks[ 0 ].innerBlocks.find(
			( block ) => block.name === 'core/buttons'
		);

	const buttonBlock = buttonsBlock?.innerBlocks[ 0 ];

	if ( ! buttonBlock ) {
		return;
	}

	callback( buttonBlock );
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
		callback: ( buttonBlock: BlockInstance ) => void
	) => {
		const justArrivedFullHeroPattern = blocks.find(
			isJustArrivedFullHeroPattern
		);

		if ( justArrivedFullHeroPattern ) {
			updateJustArrivedFullHeroPattern(
				justArrivedFullHeroPattern,
				callback
			);
		}

		const featuredCategoryCoverImagePattern = blocks.find(
			isFeaturedCategoryCoverImagePattern
		);

		if ( featuredCategoryCoverImagePattern ) {
			updateFeaturedCategoryCoverImagePattern(
				featuredCategoryCoverImagePattern,
				callback
			);
		}
		return blocks;
	};

export const PRODUCT_HERO_PATTERN_BUTTON_STYLE = {
	color: { background: '#ffffff', text: '#000000' },
};
