/**
 * Internal dependencies
 */
import { getAvailableCategories } from '../product-category/utils';

interface HTMLWPListElement extends HTMLElement {
	wpList: {
		settings: {
			addAfter: (
				returnedResponse: XMLDocument,
				ajaxSettings: object,
				wpListSettings: object
			) => void;
		};
	};
}

declare global {
	interface Window {
		wpAjax: {
			parseAjaxResponse: ( response: object ) => {
				responses?: {
					data?: string;
				}[];
			};
		};
	}
}

type NewCategory = {
	name: string;
	parent_id?: number;
};

/**
 * Creates a category in the product category list. This function can only be used where the product category taxonomy list is available (e.g. on the product edit page).
 */
const createCategory = async ( category: NewCategory ) => {
	const newCategoryInput = document.getElementById(
		'newproduct_cat'
	) as HTMLInputElement;
	const newCategoryParentSelect = document.getElementById(
		'newproduct_cat_parent'
	) as HTMLSelectElement;
	const newCategoryAddButton = document.getElementById(
		'product_cat-add-submit'
	) as HTMLButtonElement;
	const addCategoryToggle = document.getElementById(
		'product_cat-add-toggle'
	) as HTMLButtonElement;
	const categoryListElement = document.getElementById(
		'product_catchecklist'
	) as HTMLWPListElement;

	if (
		! [
			newCategoryInput,
			newCategoryParentSelect,
			newCategoryAddButton,
			addCategoryToggle,
			categoryListElement,
		].every( Boolean )
	) {
		throw new Error( 'Unable to find the category list elements' );
	}

	// show and hide the category inputs to make sure they are rendered at least once
	addCategoryToggle.click();
	addCategoryToggle.click();

	// Preserve original addAfter function for restoration after use
	const orgCatListAddAfter = categoryListElement.wpList.settings.addAfter;

	const categoryCreatedPromise = new Promise< number >( ( resolve ) => {
		categoryListElement.wpList.settings.addAfter = ( ...args ) => {
			orgCatListAddAfter( ...args );
			categoryListElement.wpList.settings.addAfter = orgCatListAddAfter;

			const parsedResponse = window.wpAjax.parseAjaxResponse( args[ 0 ] );
			if ( ! parsedResponse?.responses?.[ 0 ].data ) {
				throw new Error( 'Unable to parse the ajax response' );
			}

			const parsedHtml = new DOMParser().parseFromString(
				parsedResponse.responses[ 0 ].data,
				'text/html'
			);
			const newlyAddedCategoryCheckbox = Array.from(
				parsedHtml.querySelectorAll< HTMLInputElement >(
					'input[name="tax_input[product_cat][]"]'
				)
			).find( ( input ) => {
				return (
					input.parentElement?.textContent?.trim() === category.name
				);
			} );

			if ( ! newlyAddedCategoryCheckbox ) {
				throw new Error( 'Unable to find the newly added category' );
			}

			resolve( Number( newlyAddedCategoryCheckbox.value ) );
		};
	} );

	// Fill category name and select parent category if available
	newCategoryInput.value = category.name;
	if ( category.parent_id ) {
		const parentEl = newCategoryParentSelect.querySelector(
			'option[value="' + category.parent_id + '"]'
		) as HTMLOptionElement;
		if ( ! parentEl ) {
			throw new Error( 'Unable to find the parent category in the list' );
		}
		newCategoryParentSelect.value = category.parent_id.toString();
		parentEl.selected = true;
	}

	// click the add button to create the category
	newCategoryAddButton.click();

	return categoryCreatedPromise;
};

/**
 * Gets the list of categories to create from a given path. The path is a string of categories separated by a > character. e.g. "Clothing > Shirts > T-Shirts"
 *
 * @param categoryPath
 */
const getCategoriesToCreate = async (
	categoryPath: string
): Promise< NewCategory[] > => {
	const categories: NewCategory[] = [];
	const orderedList = categoryPath.split( ' > ' );
	const availableCategories = await getAvailableCategories();
	let parentCategoryId = 0;
	orderedList.every( ( categoryName, index ) => {
		const matchingCategory = availableCategories.find( ( category ) => {
			return (
				category.name === categoryName &&
				category.parent === parentCategoryId
			);
		} );
		if ( matchingCategory ) {
			// This is the parent category ID for the next category in the path
			parentCategoryId = matchingCategory.id;
		} else {
			categories.push( {
				name: categoryName,
				parent_id: parentCategoryId,
			} );

			for ( let i = index + 1; i < orderedList.length; i++ ) {
				categories.push( {
					name: orderedList[ i ],
				} );
			}

			return false;
		}

		return true;
	} );

	return categories;
};

/**
 * Creates categories from a given path. The path is a string of categories separated by a > character. e.g. "Clothing > Shirts > T-Shirts"
 *
 * @param categoryPath
 */
export const createCategoriesFromPath = async ( categoryPath: string ) => {
	const categoriesToCreate = await getCategoriesToCreate( categoryPath );

	while ( categoriesToCreate.length ) {
		const newCategoryId = await createCategory(
			categoriesToCreate.shift() as NewCategory
		);

		if ( categoriesToCreate.length ) {
			// Set the parent ID of the next category in the list to the ID of the newly created category so that it is created as a child of the newly created category
			categoriesToCreate[ 0 ].parent_id = newCategoryId;
		}
	}
};
