/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { recordTracksFactory, getPostId } from '../utils';

type TracksData = Record< string, string | number | null | Array< string > >;

type CategoryProps = {
	id: number;
	name: string;
	parent: number;
};
type CategoriesApiResponse = CategoryProps[];

export const recordCategoryTracks = recordTracksFactory< TracksData >(
	'category_completion',
	() => ( {
		post_id: getPostId(),
	} )
);

/**
 * Get all available categories in the store.
 *
 * @return {string[]} Array of category names in hierarchical manner where each parent category is separated by a > character. e.g. "Clothing > Shirts > T-Shirts"
 * @throws {Error} If the API request fails.
 */
export const getAvailableCategories = async (): Promise< string[] > => {
	try {
		const categories: CategoriesApiResponse =
			await apiFetch< CategoriesApiResponse >( {
				path: '/wc/v3/products/categories?per_page=100&fields=id,name,parent',
			} );

		// Create a map of categories by ID
		const categoryNamesById: Record< number, CategoryProps > =
			categories.reduce(
				( acc, category ) => ( {
					...acc,
					[ category.id ]: category,
				} ),
				{}
			);

		// Get the hierarchy string for each category
		return categories.map( ( category ) => {
			const hierarchy: string[] = [ category.name ];
			let parent = category.parent;

			// Traverse up the category hierarchy until the root category is reached
			while ( parent !== 0 ) {
				const parentCategory = categoryNamesById[ parent ];
				if ( parentCategory ) {
					hierarchy.push( parentCategory.name );
					parent = parentCategory.parent;
				} else {
					parent = 0;
				}
			}

			// Reverse the hierarchy array so that the parent category is first
			return hierarchy.reverse().join( ' > ' );
		} );
	} catch ( error ) {
		throw error;
	}
};
