/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { dispatch, useSelect } from '@wordpress/data';
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
import { THEME_SLUG } from '~/customize-store/data/constants';

export const usePatterns = () => {
	const { blockPatterns, isLoading, invalidateCache } = useSelect(
		( select ) => ( {
			blockPatterns: select(
				coreStore
				// @ts-expect-error -- No types for this exist yet.
			).getBlockPatterns() as Pattern[],
			isLoading:
				// @ts-expect-error -- No types for this exist yet.
				! select( coreStore ).hasFinishedResolution(
					'getBlockPatterns'
				),
			invalidateCache: () =>
				// @ts-expect-error -- No types for this exist yet.
				dispatch( coreStore ).invalidateResolutionForStoreSelector(
					'getBlockPatterns'
				),
		} )
	);

	return {
		blockPatterns,
		isLoading,
		invalidateCache,
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
		return ( blockPatterns || [] ).filter(
			( pattern: Pattern ) =>
				pattern.categories?.includes( category ) &&
				! pattern.name.includes( THEME_SLUG ) &&
				pattern.source !== 'pattern-directory/theme' &&
				pattern.source !== 'pattern-directory/core'
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
