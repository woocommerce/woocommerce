/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { parse } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import { useLogoAttributes } from '../hooks/use-logo-attributes';
import { setLogoWidth } from '../../utils';
import { Pattern } from '~/customize-store/types/pattern';

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
	const { attributes, isAttributesLoading } = useLogoAttributes();
	const [ currentLogoWidth, setCurrentLogoWidth ] = useState(
		attributes.width
	);

	useEffect( () => {
		if ( isAttributesLoading ) {
			return;
		}

		setCurrentLogoWidth( attributes.width );
	}, [ isAttributesLoading, attributes.width, currentLogoWidth ] );

	const patternsByCategory = useMemo( () => {
		return ( blockPatterns || [] ).filter( ( pattern: Pattern ) =>
			pattern.categories?.includes( category )
		);
	}, [ blockPatterns, category ] );

	const patternsWithBlocks = useMemo( () => {
		return patternsByCategory.map( ( pattern: Pattern ) => {
			const content = setLogoWidth( pattern.content, currentLogoWidth );

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
	}, [ patternsByCategory, currentLogoWidth ] );

	return { isLoading, patterns: patternsWithBlocks };
};
