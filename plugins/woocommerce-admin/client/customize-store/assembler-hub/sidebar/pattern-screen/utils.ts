/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Pattern } from '~/customize-store/types/pattern';
import { PATTERN_CATEGORIES } from './categories';

/**
 * Adds a 'is-added' CSS class to each pattern preview element in the pattern list that matches a block's pattern name.
 * This function iterates through an array of blocks added in the page, extracts the pattern name from each block's metadata,
 * and finds the corresponding pattern preview element in the pattern list by its ID. If found, the 'is-added' class is added to the element.
 */
export const addIsAddedClassToPatternPreview = (
	patternListEl: HTMLElement,
	blocks: BlockInstance[]
) => {
	patternListEl.querySelectorAll( '.is-added' ).forEach( ( element ) => {
		element.classList.remove( 'is-added' );
	} );

	blocks.forEach( ( block ) => {
		const patterName = block.attributes.metadata?.patternName;
		if ( ! patterName ) {
			return;
		}

		const element = patternListEl.querySelector( `[id="${ patterName }"]` );

		if ( element ) {
			element.classList.add( 'is-added' );
		}
	} );
};

const orderPatternList = {
	intro: [
		'Intro: Two column with content and image',
		'Heading with image and two columns below',
		'Fullwidth content with background image',
		'Two column with image and content',
		'Centered heading with two column text',
		'Content with button and fullwidth image',
		'Center-aligned content overlaid on an image',
		'Left-aligned content overlaid on an image',
		'Centered Content',
		'Large left-aligned heading',
		'Fullwidth image with content and button',
		'Pull right with wide image below',
	],
	about: [
		'Content right with image left',
		'Content left with image right',
		'Heading left and content right',
		'Four image grid, content on the left',
		'Content with grid of images on right',
		'Heading with two media columns',
		'Heading with content and large image below',
		'Centered heading and button',
		'Content left, image right',
		'Tall content with image left',
		'Fullwidth image, content pull right',
		'Right-aligned Content',
		'Large heading with content on right',
		'Tall content with image right',
		'Spread right, heavy text',
		'Heading with button and text',
		'Left-aligned content',
		'Pull left, fullwidth image',
	],
	services: [
		'Three columns with images and content',
		'Heading with four text sections',
		'Two columns with images',
		'Heading with six text sections',
		'Headings left, content right',
	],
} as Record< string, string[] >;

const orderByPriority = (
	aElementTitle: string,
	bElementTitle: string,
	category: string
) => {
	const aIndex = orderPatternList[ category ]?.indexOf( aElementTitle );
	const bIndex = orderPatternList[ category ]?.indexOf( bElementTitle );

	if ( aIndex === -1 && bIndex === -1 ) {
		return null;
	}

	if ( aIndex > -1 && bIndex > -1 ) {
		return aIndex - bIndex;
	}

	if ( aIndex === -1 && bIndex > -1 ) {
		return 1;
	}
	if ( bIndex === -1 && aIndex > -1 ) {
		return -1;
	}
};

/**
 * Sorts patterns by category and priority based on the orderPatternList object . For 'intro' and 'about' categories
 * prioritized DotCom Patterns. For intro category, it also prioritizes the "centered-content-with-image-below" pattern.
 * For other categories, it simply sorts patterns to prioritize Woo Patterns.
 */
export const sortPatternsByCategory = (
	patterns: Pattern[],
	category: keyof typeof PATTERN_CATEGORIES
) => {
	const prefix = 'woocommerce-blocks';
	if ( category === 'intro' || category === 'about' ) {
		return patterns.sort( ( a, b ) => {
			if (
				a.name ===
				'woocommerce-blocks/centered-content-with-image-below'
			) {
				return -1;
			}

			if (
				b.name ===
				'woocommerce-blocks/centered-content-with-image-below'
			) {
				return 1;
			}

			const priority = orderByPriority( a.title, b.title, category );

			if ( typeof priority === 'number' ) {
				return priority;
			}

			if ( a.name.includes( prefix ) && ! b.name.includes( prefix ) ) {
				return 1;
			}

			if ( ! a.name.includes( prefix ) && b.name.includes( prefix ) ) {
				return -1;
			}

			// If neither title is in the list, keep their original order
			return 0;
		} );
	}

	return patterns.sort( ( a, b ) => {
		const priority = orderByPriority( a.title, b.title, category );

		if ( typeof priority === 'number' ) {
			return priority;
		}

		if ( a.name.includes( prefix ) && ! b.name.includes( prefix ) ) {
			return -1;
		}
		if ( ! a.name.includes( prefix ) && b.name.includes( prefix ) ) {
			return 1;
		}
		return 0;
	} );
};
