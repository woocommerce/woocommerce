// This logic is copied from: https://github.com/WordPress/gutenberg/blob/29c620c79a4c3cfa4c1300cd3c9eeeb06709d3e0/packages/block-editor/src/components/block-toolbar/shuffle.js

/**
 * External dependencies
 */

import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Path, SVG } from '@wordpress/components';
import {
	unlock,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/edit-site/build-module/lock-unlock';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	store as blockEditorStore,
	// @ts-expect-error missing type
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { PatternWithBlocks } from '~/customize-store/types/pattern';

// This is the icon that is used in the Shuffle button. Currently we are using an outdated version of @wordpress/icons.
// import { shuffle } from '@wordpress/icons';

// Copied-pasted from a recent version of @wordpress/icons
const shuffleIcon = (
	<SVG viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/SVG">
		<Path d="M17.192 6.75L15.47 5.03l1.06-1.06 3.537 3.53-3.537 3.53-1.06-1.06 1.723-1.72h-3.19c-.602 0-.993.202-1.28.498-.309.319-.538.792-.695 1.383-.13.488-.222 1.023-.296 1.508-.034.664-.116 1.413-.303 2.117-.193.721-.513 1.467-1.068 2.04-.575.594-1.359.954-2.357.954H4v-1.5h4.003c.601 0 .993-.202 1.28-.498.308-.319.538-.792.695-1.383.149-.557.216-1.093.288-1.662l.039-.31a9.653 9.653 0 0 1 .272-1.653c.193-.722.513-1.467 1.067-2.04.576-.594 1.36-.954 2.358-.954h3.19zM8.004 6.75c.8 0 1.46.23 1.988.628a6.24 6.24 0 0 0-.684 1.396 1.725 1.725 0 0 0-.024-.026c-.287-.296-.679-.498-1.28-.498H4v-1.5h4.003zM12.699 14.726c-.161.459-.38.94-.684 1.396.527.397 1.188.628 1.988.628h3.19l-1.722 1.72 1.06 1.06L20.067 16l-3.537-3.53-1.06 1.06 1.723 1.72h-3.19c-.602 0-.993-.202-1.28-.498a1.96 1.96 0 0 1-.024-.026z" />
	</SVG>
);

export default function Shuffle( { clientId }: { clientId: string } ) {
	const {
		categories,
		patterns,
		patternName,
	}: {
		categories: string[];
		patterns: PatternWithBlocks[];
		patternName: string;
	} = useSelect(
		( select ) => {
			// @ts-expect-error missing type
			const { getBlockAttributes, getBlockRootClientId } =
				select( blockEditorStore );
			const attributes = getBlockAttributes( clientId );
			const _categories = attributes?.metadata?.categories || [];
			const _patternName = attributes?.metadata?.patternName;
			const rootBlock = getBlockRootClientId( clientId );
			const _patterns = unlock(
				select( blockEditorStore )
			).__experimentalGetAllowedPatterns( rootBlock );

			return {
				categories: _categories,
				patterns: _patterns,
				patternName: _patternName,
			};
		},
		[ clientId ]
	);
	// @ts-expect-error missing type
	const { replaceBlocks } = useDispatch( blockEditorStore );
	const sameCategoryPatternsWithSingleWrapper = useMemo( () => {
		if (
			! categories ||
			categories.length === 0 ||
			! patterns ||
			patterns.length === 0
		) {
			return [];
		}
		return patterns.filter( ( pattern ) => {
			return (
				// Check if the pattern has only one top level block,
				// otherwise we may shuffle to pattern that will not allow to continue shuffling.
				pattern.blocks.length === 1 &&
				pattern.categories?.some( ( category ) => {
					return categories.includes( category );
				} )
			);
		} );
	}, [ categories, patterns ] );
	if ( sameCategoryPatternsWithSingleWrapper.length === 0 ) {
		return null;
	}

	function getNextPattern() {
		const numberOfPatterns = sameCategoryPatternsWithSingleWrapper.length;
		const patternIndex = sameCategoryPatternsWithSingleWrapper.findIndex(
			( { name } ) => name === patternName
		);
		const nextPatternIndex =
			patternIndex + 1 < numberOfPatterns ? patternIndex + 1 : 0;
		return sameCategoryPatternsWithSingleWrapper[ nextPatternIndex ];
	}

	return (
		<Button
			label={ __( 'Shuffle', 'woocommerce' ) }
			icon={ shuffleIcon }
			onClick={ () => {
				const nextPattern = getNextPattern();
				// @ts-expect-error - attributes is marked as readonly.
				nextPattern.blocks[ 0 ].attributes = {
					...nextPattern.blocks[ 0 ].attributes,
					metadata: {
						...nextPattern.blocks[ 0 ].attributes.metadata,
						categories,
					},
				};
				replaceBlocks( clientId, nextPattern.blocks );
			} }
		/>
	);
}
