/**
 * External dependencies
 */
import { useQuery } from '@woocommerce/navigation';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SidebarPatternScreen } from '../pattern-screen/sidebar-pattern-screen';

import './style.scss';
import { PATTERN_CATEGORIES } from '../pattern-screen/categories';

const getPatternCategoryByPath = ( path: string ) => {
	// Define the regex pattern to match everything after "/homepage/"
	const regex = /\/homepage\/(.*)/;

	// Apply the regex pattern to the pathname part of the URL
	const match = path.match( regex );

	// If a match is found, return the captured group (everything after "/homepage/")
	if ( match && match[ 1 ] ) {
		return match[ 1 ];
	}

	const pattern_category = match && match[ 1 ];

	if (
		pattern_category &&
		Object.keys( PATTERN_CATEGORIES ).includes( pattern_category )
	) {
		return pattern_category;
	}

	return null;
};

export const SidebarNavigationExtraScreen = () => {
	const { path } = useQuery();

	const patternCategory = useMemo(
		() => ( path ? getPatternCategoryByPath( path ) : null ),
		[ path ]
	);

	if ( patternCategory ) {
		return (
			<div className="woocommerce-customize-store-edit-site-layout__sidebar-extra">
				<SidebarPatternScreen category={ patternCategory } />;
			</div>
		);
	}

	return null;
};
