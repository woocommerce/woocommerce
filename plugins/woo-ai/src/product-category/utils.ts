/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { getPostId, recordTracksFactory, decodeHtmlEntities } from '../utils';

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
 * @return {string[]} Array of categories.
 * @throws {Error} If the API request fails.
 */
export const getAvailableCategories =
	async (): Promise< CategoriesApiResponse > => {
		const results = await apiFetch< CategoriesApiResponse >( {
			path: '/wc/v3/products/categories?per_page=100&fields=id,name,parent',
		} );

		results.forEach( ( category ) => {
			category.name = decodeHtmlEntities( category.name );
		} );

		return results;
	};

/**
 * Get all available categories in the store as a hierarchical list of strings.
 *
 * @return {string[]} Array of category names in hierarchical manner where each parent category is separated by a > character. e.g. "Clothing > Shirts > T-Shirts"
 * @throws {Error} If the API request fails.
 */
export const getAvailableCategoryPaths = async (): Promise< string[] > => {
	const categories: CategoriesApiResponse = await getAvailableCategories();

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
};
