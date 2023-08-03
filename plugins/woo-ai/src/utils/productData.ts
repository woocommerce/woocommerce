/**
 * Internal dependencies
 */
import { Attribute } from './types';
import { getTinyContent } from '.';

export const getCategories = (): string[] => {
	return Array.from(
		document.querySelectorAll(
			'#taxonomy-product_cat input[name="tax_input[product_cat][]"]'
		)
	)
		.filter(
			( item ) =>
				window.getComputedStyle( item, ':before' ).content !== 'none'
		)
		.map( ( item ) => item.nextSibling?.nodeValue?.trim() || '' )
		.filter( Boolean );
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
