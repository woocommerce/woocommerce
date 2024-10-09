/**
 * Helper function to select a checkbox if it exists within an element
 *
 * @param element - The DOM element to check for a checkbox
 */
const checkFirstCheckboxInElement = ( element: HTMLElement ) => {
	// Select the checkbox input element if it exists
	const checkboxElement: HTMLInputElement | null = element.querySelector(
		'label > input[type="checkbox"]'
	);

	// If the checkbox exists, check it and trigger a 'change' event
	if ( checkboxElement ) {
		checkboxElement.checked = true;
		checkboxElement.dispatchEvent( new Event( 'change' ) );
	}
};

/**
 * Recursive function to select categories and their children based on the provided ordered list
 *
 * @param orderedCategories - An ordered list of categories to be selected, starting with the top-level category and ending with the lowest-level category.
 * @param categoryElements  - A list of HTML List elements representing the categories
 */
const selectCategoriesRecursively = (
	orderedCategories: string[],
	categoryElements: HTMLLIElement[]
) => {
	const categoryToSelect = orderedCategories[ 0 ];

	// Find the HTML element that matches the category to be selected
	const selectedCategoryElement = categoryElements.find(
		( element ) =>
			element.querySelector( ':scope > label' )?.textContent?.trim() ===
			categoryToSelect
	);

	// If the category to be selected doesn't exist, terminate the function
	if ( ! selectedCategoryElement ) {
		return;
	}

	checkFirstCheckboxInElement( selectedCategoryElement );

	// Select all the child categories, if they exist
	const subsequentCategories: string[] = orderedCategories.slice( 1 );
	const childCategoryElements: HTMLLIElement[] = Array.from(
		selectedCategoryElement.querySelectorAll( 'ul.children > li' )
	);

	if ( subsequentCategories.length && childCategoryElements.length ) {
		selectCategoriesRecursively(
			subsequentCategories,
			childCategoryElements
		);
	}
};

/**
 * Main function to select a category and its children from a provided category path
 *
 * @param categoryPath - The path to the category, each level separated by a > character.
 *                     e.g. "Clothing > Shirts > T-Shirts"
 */
export const selectCategory = ( categoryPath: string ) => {
	const categories = categoryPath.split( '>' ).map( ( name ) => name.trim() );
	const categoryListElements: HTMLLIElement[] = Array.from(
		document.querySelectorAll( '#product_catchecklist > li' )
	);

	selectCategoriesRecursively( categories, categoryListElements );
};
