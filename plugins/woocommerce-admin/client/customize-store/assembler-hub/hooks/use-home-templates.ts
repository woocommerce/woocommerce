/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { parse } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { usePatterns, Pattern, PatternWithBlocks } from './use-patterns';
import { HOMEPAGE_TEMPLATES } from '~/customize-store/data/homepageTemplates';

export const getTemplatePatterns = (
	template: string[],
	patternsByName: Record< string, Pattern >
) =>
	template
		.map( ( patternName: string ) => {
			const pattern = patternsByName[ patternName ];
			if ( pattern && pattern.content ) {
				return {
					...pattern,
					// @ts-ignore - Passing options is valid, but not in the type.
					blocks: parse( pattern.content, {
						__unstableSkipMigrationLogs: true,
					} ),
				};
			}
			return null;
		} )
		.filter( ( pattern ) => pattern !== null ) as PatternWithBlocks[];

export const patternsToNameMap = ( blockPatterns: Pattern[] ) =>
	blockPatterns.reduce(
		( acc: Record< string, Pattern >, pattern: Pattern ) => {
			acc[ pattern.name ] = pattern;
			return acc;
		},
		{}
	);

// Returns home template patterns excluding header and footer.
export const useHomeTemplates = () => {
	const { blockPatterns, isLoading } = usePatterns();

	const patternsByName = useMemo(
		() => patternsToNameMap( blockPatterns ),
		[ blockPatterns ]
	);

	const homeTemplates = useMemo( () => {
		if ( isLoading ) return {};
		const recommendedTemplates = HOMEPAGE_TEMPLATES;

		return Object.entries( recommendedTemplates ).reduce(
			(
				acc: Record< string, PatternWithBlocks[] >,
				[ templateName, template ]
			) => {
				if ( templateName in recommendedTemplates ) {
					acc[ templateName ] = getTemplatePatterns(
						template.blocks,
						patternsByName
					);
				}
				return acc;
			},
			{}
		);
	}, [ isLoading, patternsByName ] );

	return {
		homeTemplates,
		isLoading,
	};
};
