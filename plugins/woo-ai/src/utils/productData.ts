/**
 * Internal dependencies
 */
import { Attribute, ProductData } from './types';
import { getTinyContent } from '../utils/tiny-tools';

const isElementVisible = ( element: HTMLElement ) =>
	! ( window.getComputedStyle( element ).display === 'none' );

const getCategories = (): string[] => {
	const categoryCheckboxEls: NodeListOf< HTMLInputElement > =
		document.querySelectorAll(
			'#taxonomy-product_cat input[name="tax_input[product_cat][]"]:checked'
		);

	const tempCategories: string[] = [];

	categoryCheckboxEls.forEach( ( el ) => {
		if ( ! el.value.length ) {
			return;
		}

		tempCategories.push( el.value );
	} );

	return tempCategories;
};

const getTags = (): string[] => {
	const tagsEl: HTMLTextAreaElement | null = document.querySelector(
		'textarea[name="tax_input[product_tag]"]'
	);

	const tags = tagsEl ? tagsEl.value.split( ',' ) : [];

	return tags.filter( ( tag ) => tag !== '' );
};

const getAttributes = (): Attribute[] => {
	const attributeSelectEls: NodeListOf< HTMLSelectElement > =
		document.querySelectorAll(
			"#product_attributes select[name^='attribute_values']"
		);

	const tempAttributes: Attribute[] = [];

	attributeSelectEls.forEach( ( el: HTMLSelectElement ) => {
		const attributeName =
			el.getAttribute( 'data-taxonomy' )?.replace( 'pa_', '' ) || '';

		const attributeValues = Array.from( el.selectedOptions )
			.map( ( option ) => option.text )
			.join( ',' );

		if ( ! attributeValues || ! attributeName ) {
			return;
		}

		tempAttributes.push( {
			name: attributeName,
			value: attributeValues,
		} );
	} );

	return tempAttributes;
};

const getDescription = (): string => {
	const isBlockEditor =
		document.querySelectorAll( '.block-editor' ).length > 0;

	if ( ! isBlockEditor ) {
		const content = document.querySelector(
			'#content'
		) as HTMLInputElement;
		const tinyContent = getTinyContent();
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

const getProductName = (): string => {
	const productNameEl: HTMLInputElement | null =
		document.querySelector( '#title' );

	return productNameEl ? productNameEl.value : '';
};

const getProductType = () => {
	const productTypeEl: HTMLInputElement | null =
		document.querySelector( '#product-type' );

	return productTypeEl ? productTypeEl.value : '';
};

export const productData = (): ProductData => {
	return {
		name: getProductName(),
		categories: getCategories(),
		tags: getTags(),
		attributes: getAttributes(),
		description: getDescription(),
		product_type: getProductType(),
		is_downloadable: (
			document.querySelector( '#_downloadable' ) as HTMLInputElement
		 )?.checked
			? 'Yes'
			: 'No',
		is_virtual: (
			document.querySelector( '#_virtual' ) as HTMLInputElement
		 )?.checked
			? 'Yes'
			: 'No',
	};
};
