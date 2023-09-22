/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import { useMemo } from '@wordpress/element';
import { BlockInstance, parse } from '@wordpress/blocks';

export type Pattern = {
	blockTypes: string[];
	categories: string[];
	content: string;
	name: string;
	source: string;
	title: string;
};

export type PatternWithBlocks = Pattern & {
	blocks: BlockInstance[];
};

export const usePatterns = () => {
	const { blockPatterns, isLoading } = useSelect(
		( select ) => ( {
			blockPatterns: select(
				coreStore
				// @ts-ignore - This is valid.
			).getBlockPatterns() as Pattern[],
			isLoading:
				// @ts-ignore - This is valid.
				! select( coreStore ).hasFinishedResolution(
					'getBlockPatterns'
				),
		} ),
		[]
	);

	return {
		blockPatterns,
		isLoading,
	};
};

export const usePatternsByCategory = ( category: string ) => {
	const { blockPatterns, isLoading } = usePatterns();

	const patternsByCategory: PatternWithBlocks[] = useMemo( () => {
		return ( blockPatterns || [] )
			.filter( ( pattern: Pattern ) =>
				pattern.categories?.includes( category )
			)
			.map( ( pattern: Pattern ) => {
				return {
					...pattern,
					// @ts-ignore - Passing options is valid, but not in the type.
					blocks: parse( pattern.content, {
						__unstableSkipMigrationLogs: true,
					} ),
				};
			} );
	}, [ blockPatterns, category ] );

	return { isLoading, patterns: patternsByCategory };
};
