/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Returns the deprecation warning copy shown in the block editor.
 */
export function getDeprecatedBlockWarning( blockName: string ): string {
	return `This version of the ${ blockName } block is outdated. You can delete it and use the Product Collection block instead.`;
}

/**
 * Resolves a product category term ID by name via the Store API.
 */
export async function getProductCategoryIdByName(
	page: Page,
	name: string
): Promise< number > {
	return page.evaluate( async ( categoryName ) => {
		const categories = await window.wp.apiFetch( {
			path: `/wc/store/v1/products/categories?search=${ encodeURIComponent(
				categoryName
			) }`,
		} );
		return categories[ 0 ]?.id;
	}, name );
}

/**
 * Resolves all terms for a product attribute.
 */
export async function getAllAttributeTerms(
	page: Page,
	attributeSlug: string
): Promise< Array< { id: number; attr_slug: string } > > {
	return page.evaluate( async ( attrSlug ) => {
		const attributes = await window.wp.apiFetch( {
			path: '/wc/store/v1/products/attributes',
		} );
		const attribute = attributes.find(
			( attr: { taxonomy: string } ) => attr.taxonomy === attrSlug
		);

		if ( ! attribute ) {
			throw new Error( `Attribute not found: ${ attrSlug }` );
		}

		const terms = await window.wp.apiFetch( {
			path: `/wc/store/v1/products/attributes/${ attribute.id }/terms?per_page=100`,
		} );
		return terms.map( ( term: { id: number } ) => ( {
			id: term.id,
			attr_slug: attrSlug,
		} ) );
	}, attributeSlug );
}

/**
 * Resolves a product tag term ID by name via the Store API.
 */
export async function getProductTagIdByName(
	page: Page,
	name: string
): Promise< number > {
	return page.evaluate( async ( tagName ) => {
		const tags = await window.wp.apiFetch( {
			path: `/wc/store/v1/products/tags?search=${ encodeURIComponent(
				tagName
			) }`,
		} );
		return tags[ 0 ]?.id;
	}, name );
}
