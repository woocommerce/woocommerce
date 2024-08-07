/**
 * External dependencies
 */
import { capitalize } from 'lodash';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Button, Path, SVG, ToolbarGroup } from '@wordpress/components';
import {
	store as blockEditorStore,
	// @ts-expect-error missing type
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PatternWithBlocks } from '~/customize-store/types/pattern';
import { PATTERN_CATEGORIES } from '../sidebar/pattern-screen/categories';
import { usePatternsByCategory } from '../hooks/use-patterns';
import { trackEvent } from '~/customize-store/tracking';

// This is the icon that is used in the Shuffle button. Currently we are using an outdated version of @wordpress/icons.
// import { shuffle } from '@wordpress/icons';

// Copied-pasted from a recent version of @wordpress/icons
const shuffleIcon = (
	<SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/SVG">
		<Path d="M17.192 6.75L15.47 5.03l1.06-1.06 3.537 3.53-3.537 3.53-1.06-1.06 1.723-1.72h-3.19c-.602 0-.993.202-1.28.498-.309.319-.538.792-.695 1.383-.13.488-.222 1.023-.296 1.508-.034.664-.116 1.413-.303 2.117-.193.721-.513 1.467-1.068 2.04-.575.594-1.359.954-2.357.954H4v-1.5h4.003c.601 0 .993-.202 1.28-.498.308-.319.538-.792.695-1.383.149-.557.216-1.093.288-1.662l.039-.31a9.653 9.653 0 0 1 .272-1.653c.193-.722.513-1.467 1.067-2.04.576-.594 1.36-.954 2.358-.954h3.19zM8.004 6.75c.8 0 1.46.23 1.988.628a6.24 6.24 0 0 0-.684 1.396 1.725 1.725 0 0 0-.024-.026c-.287-.296-.679-.498-1.28-.498H4v-1.5h4.003zM12.699 14.726c-.161.459-.38.94-.684 1.396.527.397 1.188.628 1.988.628h3.19l-1.722 1.72 1.06 1.06L20.067 16l-3.537-3.53-1.06 1.06 1.723 1.72h-3.19c-.602 0-.993-.202-1.28-.498a1.96 1.96 0 0 1-.024-.026z" />
	</SVG>
);

const getCategoryLabelFromCategories = ( categories: string[] ) => {
	for ( const category in PATTERN_CATEGORIES ) {
		if ( categories.includes( category ) ) {
			return PATTERN_CATEGORIES[
				category as keyof typeof PATTERN_CATEGORIES
			].label;
		}
	}
};

/**
 * Selects a random pattern from the provided array that is not the current pattern.
 * If the randomly selected pattern is the same as the current, it attempts to select the next pattern in the array.
 * If the current pattern is the last in the array, it selects the first pattern.
 * If there's only one pattern in the array, it will return that pattern.
 */
const getNextPattern = (
	patterns: PatternWithBlocks[],
	patternName: string
) => {
	const numberOfPatterns = patterns.length;
	const currentPatternIndex = patterns.findIndex(
		( { name } ) => name === patternName
	);
	const nextPatternIndex = Math.floor( Math.random() * numberOfPatterns );

	if ( nextPatternIndex !== currentPatternIndex ) {
		return patterns[ nextPatternIndex ];
	}

	if ( currentPatternIndex === nextPatternIndex ) {
		if ( nextPatternIndex === 0 ) {
			return patterns[ 1 ];
		}

		if ( nextPatternIndex === numberOfPatterns ) {
			return patterns[ 0 ];
		}

		return patterns[ nextPatternIndex - 1 ];
	}

	return patterns[ 0 ];
};

export default function Shuffle( { clientId }: { clientId: string } ) {
	const {
		category,
		patternName,
	}: {
		category: string;
		patternName: string;
	} = useSelect(
		( select ) => {
			// @ts-expect-error missing type
			const { getBlockAttributes } = select( blockEditorStore );
			const attributes = getBlockAttributes( clientId );
			const categories = attributes?.metadata?.categories;
			// We know that the category is one of the keys of PATTERN_CATEGORIES.
			const _category = Object.keys( PATTERN_CATEGORIES ).find( ( cat ) =>
				categories?.includes( cat )
			) as string;

			const _patternName = attributes?.metadata?.patternName;

			return {
				category: _category,
				patternName: _patternName,
			};
		},
		[ clientId ]
	);

	const { patterns } = usePatternsByCategory( category );

	// @ts-expect-error missing type
	const { replaceBlocks } = useDispatch( blockEditorStore );

	// We need at least two patterns to shuffle.
	if ( patterns.length < 2 ) {
		return null;
	}

	const categoryLabel = getCategoryLabelFromCategories( [ category ] );

	return (
		<ToolbarGroup className="woocommerce-customize-your-store-toolbar-shuffle-container">
			<Button
				icon={ shuffleIcon }
				label={ __( 'Shuffle', 'woocommerce' ) }
				onClick={ () => {
					const nextPattern = getNextPattern( patterns, patternName );
					// @ts-expect-error - attributes is marked as readonly.
					nextPattern.blocks[ 0 ].attributes = {
						...nextPattern.blocks[ 0 ].attributes,
						metadata: {
							...nextPattern.blocks[ 0 ].attributes.metadata,
							categories: [ category ],
							patternName: nextPattern.name,
						},
					};
					replaceBlocks( clientId, nextPattern.blocks );
					trackEvent(
						'customize_your_store_assembler_pattern_shuffle_click',
						{ category, pattern: nextPattern.name }
					);
				} }
			>
				{ categoryLabel && (
					<span>{ capitalize( categoryLabel ) }</span>
				) }
			</Button>
		</ToolbarGroup>
	);
}
