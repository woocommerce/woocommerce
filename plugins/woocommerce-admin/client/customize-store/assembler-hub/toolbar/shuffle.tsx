// This logic is copied from: https://github.com/WordPress/gutenberg/blob/29c620c79a4c3cfa4c1300cd3c9eeeb06709d3e0/packages/block-editor/src/components/block-toolbar/shuffle.js

/**
 * External dependencies
 */

import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { shuffle } from '@wordpress/icons';
import { Button } from '@wordpress/components';
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
			icon={ shuffle }
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
