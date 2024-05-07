/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

export const findButtonBlockInsideCoverBlockProductHeroPatternAndUpdate = (
	blocks: BlockInstance[],
	callback: ( buttonBlock: BlockInstance ) => void
) => {
	const clonedBlocks = structuredClone( blocks );
	const coverBlock = clonedBlocks.find(
		( block ) =>
			block.name === 'core/cover' &&
			block.attributes.url.includes(
				'music-black-and-white-white-photography.jpg'
			)
	);

	const buttonsBlock = coverBlock?.innerBlocks[ 0 ].innerBlocks.find(
		( block ) => block.name === 'core/buttons'
	);

	const buttonBlock = buttonsBlock?.innerBlocks[ 0 ];

	if ( ! buttonBlock ) {
		return clonedBlocks;
	}

	callback( buttonBlock );
	return clonedBlocks;
};

export const PRODUCT_HERO_PATTERN_BUTTON_STYLE = {
	color: { background: '#ffffff', text: '#000000' },
};
