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
/**
 * Internal dependencies
 */
import { useLogoAttributes } from '../hooks/use-logo-attributes';
import { setLogoWidth } from '../../utils';

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
	const { attributes } = useLogoAttributes();

	const patternsByCategory: PatternWithBlocks[] = useMemo( () => {
		return ( blockPatterns || [] )
			.filter( ( pattern: Pattern ) =>
				pattern.categories?.includes( category )
			)
			.map( ( pattern: Pattern ) => {
				const content = setLogoWidth(
					pattern.content,
					attributes.width
				);

				return {
					...pattern,
					content,
					// Set the logo width to the current logo width so that user changes are not lost.

					blocks: parse(
						content,
						// @ts-ignore - Passing options is valid, but not in the type.
						{
							__unstableSkipMigrationLogs: true,
						}
					),
				};
			} );
	}, [ blockPatterns, category, attributes ] );

	return { isLoading, patterns: patternsByCategory };
};
