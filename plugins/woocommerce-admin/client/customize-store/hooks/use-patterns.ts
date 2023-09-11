/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useMemo } from '@wordpress/element';
import { BlockInstance, parse } from '@wordpress/blocks';

type Pattern = {
	blockTypes: string[];
	categories: string[];
	content: string;
	name: string;
	source: string;
	title: string;
};

type PatternWithBlocks = Pattern & {
	blocks: BlockInstance[];
};

export const usePatternsByCategory = ( category: string ) => {
	const { blockPatterns } = useSelect(
		( select ) => ( {
			blockPatterns: select( coreStore ).getBlockPatterns(),
		} ),
		[]
	);

	const patternsByCategory: PatternWithBlocks[] = useMemo( () => {
		return ( blockPatterns || [] )
			.filter( ( pattern: Pattern ) =>
				pattern.categories?.includes( category )
			)
			.map( ( pattern: Pattern ) => {
				return {
					...pattern,
					blocks: parse( pattern.content, {
						__unstableSkipMigrationLogs: true,
					} ),
				};
			} );
	}, [ blockPatterns, category ] );

	return patternsByCategory;
};
