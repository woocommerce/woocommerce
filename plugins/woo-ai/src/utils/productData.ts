/**
 * Internal dependencies
 */
import { Attribute } from './types';
import { getTinyContent } from '.';

export enum ProductProps {
	Name = 'name',
	Description = 'description',
	Categories = 'categories',
	Tags = 'tags',
	Attributes = 'attributes',
}

/**
 * Retrieves a hierarchy string for the specified category element. This includes the category label and all parent categories separated by a > character.
 *
 * @param {HTMLInputElement} categoryElement - The category element to get the hierarchy string for.
 * @return {string} The hierarchy string for the specified category element. e.g. "Clothing > Shirts > T-Shirts"
 */
const getCategoryHierarchy = ( categoryElement: HTMLElement ) => {
	let hierarchy = '';
	let parentElement = categoryElement.parentElement;

	// Traverse up the DOM Tree until a category list item (LI) is found
	while ( parentElement ) {
		const isListItem = parentElement.tagName.toUpperCase() === 'LI';
		const isRootList = parentElement.id === 'product_catchecklist';

		if ( isListItem ) {
			const categoryLabel =
				parentElement.querySelector( 'label' )?.innerText.trim() || '';

			if ( categoryLabel ) {
				hierarchy = hierarchy
					? `${ categoryLabel } > ${ hierarchy }`
					: categoryLabel;
			} else {
				break;
			}
		}

		if ( isRootList ) {
			// If the root category list is found, it means we have reached the top of the hierarchy
			break;
		}

		parentElement = parentElement.parentElement;
	}

	return hierarchy;
};

/**
 * Function to get selected categories in hierarchical manner.
 *
 * @return {string[]} Array of category hierarchy strings for each selected category.
 */
export const getCategories = (): string[] => {
	// Get all the selected category checkboxes
	const checkboxes: NodeListOf< HTMLInputElement > =
		document.querySelectorAll(
			'#taxonomy-product_cat input[type="checkbox"][name="tax_input[product_cat][]"]'
		);
	const categoryElements = Array.from( checkboxes );
	const selectedCategories = categoryElements.filter(
		( category ) => category.checked
	);

	// Get the hierarchy string for each selected category and filter out any empty strings
	return selectedCategories.map( getCategoryHierarchy ).filter( Boolean );
};

const isElementVisible = ( element: HTMLElement ) =>
	! ( window.getComputedStyle( element ).display === 'none' );

export const getTags = (): string[] => {
	const tagsEl: HTMLTextAreaElement | null = document.querySelector(
		'textarea[name="tax_input[product_tag]"]'
	);

	const tags = tagsEl ? tagsEl.value.split( ',' ) : [];

	return tags.filter( ( tag ) => tag !== '' );
};

export const getAttributes = (): Attribute[] => {
	const attributeContainerEls = Array.from(
		document.querySelectorAll( '.woocommerce_attribute' )
	);

	return attributeContainerEls.reduce( ( acc, item ) => {
		const attributeGetters = {
			local: {
				name: () =>
					(
						item.querySelector(
							'.woocommerce_attribute_data input.attribute_name'
						) as HTMLInputElement | null
					 )?.value,
				values: () =>
					item
						.querySelector( '.woocommerce_attribute_data textarea' )
						?.textContent?.split( '|' )
						.map( ( attrName ) => attrName.trim() ),
			},
			global: {
				name: () =>
					(
						item.querySelector(
							'.attribute_name > strong'
						) as Element | null
					 )?.textContent,
				values: () =>
					Array.from(
						item.querySelectorAll(
							'select.attribute_values option'
						) ?? []
					).map( ( option ) => option.textContent?.trim() ?? '' ),
			},
		};

		const type = Boolean( attributeGetters.global.name() )
			? 'global'
			: 'local';

		const name = attributeGetters[ type ].name();
		const values = attributeGetters[ type ].values()?.filter( Boolean );
		if ( name && values ) {
			acc.push( { name, values } );
		}
		return acc;
	}, [] as Attribute[] );
};

export const getDescription = (): string => {
	const isBlockEditor =
		document.querySelectorAll( '.block-editor' ).length > 0;

	if ( ! isBlockEditor ) {
		const content = document.querySelector(
			'#content'
		) as HTMLInputElement;
		const tinyContent = getTinyContent( 'content', { format: 'text' } );
		if ( content && isElementVisible( content ) ) {
			return content.value;
		} else if ( tinyContent ) {
			return tinyContent;
		}
	}

	return (
		document.querySelector(
			'.block-editor-rich-text__editable'
		) as HTMLInputElement
	 )?.value;
};

export const getProductName = (): string => {
	const productNameEl: HTMLInputElement | null =
		document.querySelector( '#title' );

	return productNameEl ? productNameEl.value : '';
};

export const getProductType = () => {
	const productTypeEl: HTMLInputElement | null =
		document.querySelector( '#product-type' );

	return productTypeEl ? productTypeEl.value : '';
};

export const getPublishingStatus = () =>
	( document.querySelector( '#post_status' ) as HTMLInputElement )?.value;

export const isProductVirtual = () =>
	( document.querySelector( '#_virtual' ) as HTMLInputElement )?.checked;

export const isProductDownloadable = () =>
	( document.querySelector( '#_downloadable' ) as HTMLInputElement )?.checked;

/**
 * Get all available child categories for a given category element
 *
 * @return {string[]} Array of category names, including the top level category, e.g. [ 'Clothing', 'Clothing > T-Shirts', 'Clothing > T-Shirts > Mens' ]
 * @param  categoryEl - The category element to get children from. This should be a list item element.
 */
const getChildrenCategories = ( categoryEl: HTMLLIElement ): string[] => {
	const name = categoryEl.querySelector( 'label' )?.textContent?.trim() || '';
	const children: HTMLLIElement[] = Array.from(
		categoryEl.querySelectorAll( '.children > li' )
	);
	const all_children: string[] = [ name ];

	if ( children.length ) {
		children.forEach( ( child: HTMLLIElement ) => {
			all_children.push(
				...getChildrenCategories( child ).map(
					( child_path ) => `${ name } > ${ child_path }`
				)
			);
		} );
	}

	return all_children;
};

/**
 * Get all available categories in the product category selector
 *
 * @return {string[]} Array of category names
 */
export const getAllAvailableCategories = (): string[] => {
	const results: string[] = [];
	const topLevelCategories: HTMLLIElement[] = Array.from(
		document.querySelectorAll( '#product_catchecklist > li' )
	);

	topLevelCategories.forEach( ( category: HTMLLIElement ) => {
		results.push( ...getChildrenCategories( category ) );
	} );

	return results;
};
