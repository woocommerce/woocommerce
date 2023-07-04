/**
 * Internal dependencies
 */
import { Attribute } from './types';
import { getTinyContent } from '.';

export const getCategories = () =>
	Array.from(
		document.querySelectorAll(
			'#taxonomy-product_cat input[name="tax_input[product_cat][]"]'
		)
	)
		.filter(
			( item ) =>
				window.getComputedStyle( item, ':before' ).content !== 'none'
		)
		.map( ( item ) => item?.nextSibling?.nodeValue?.trim() );

const isElementVisible = ( element: HTMLElement ) =>
	! ( window.getComputedStyle( element ).display === 'none' );

export const getTags = (): string[] => {
	const tagsEl: HTMLTextAreaElement | null = document.querySelector(
		'textarea[name="tax_input[product_tag]"]'
	);

	const tags = tagsEl ? tagsEl.value.split( ',' ) : [];

	return tags.filter( ( tag ) => tag !== '' );
};

// TODO: Not working in my testing when manually adding attributes.
export const getAttributes = (): Attribute[] => {
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

export const getDescription = (): string => {
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
