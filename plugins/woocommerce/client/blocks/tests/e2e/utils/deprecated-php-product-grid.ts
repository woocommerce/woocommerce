/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Returns the deprecation warning copy shown in the block editor.
 */
export function getDeprecatedBlockWarning( blockName: string ): string {
	return `This version of the ${ blockName } block is outdated. Upgrade to the Product Collection block.`;
}

/**
 * Updates attributes on the first block matching the given slug.
 */
export async function updateBlockAttributesBySlug(
	page: Page,
	slug: string,
	attributes: Record< string, unknown >
): Promise< void > {
	await page.evaluate(
		( { blockSlug, attrs } ) => {
			const select = window.wp.data.select( 'core/block-editor' );
			const dispatch = window.wp.data.dispatch( 'core/block-editor' );

			const findBlock = (
				blockList: Array< {
					clientId: string;
					name: string;
					innerBlocks: Array< unknown >;
				} >
			) => {
				for ( const block of blockList ) {
					if ( block.name === blockSlug ) {
						return block;
					}
					const inner = findBlock(
						block.innerBlocks as Array< {
							clientId: string;
							name: string;
							innerBlocks: Array< unknown >;
						} >
					);
					if ( inner ) {
						return inner;
					}
				}
				return null;
			};

			const block = findBlock( select.getBlocks() );
			if ( block ) {
				dispatch.updateBlockAttributes( block.clientId, attrs );
			}
		},
		{ blockSlug: slug, attrs: attributes }
	);
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
 * Resolves a product ID by slug via the Store API.
 */
export async function getProductIdBySlug(
	page: Page,
	slug: string
): Promise< number > {
	return page.evaluate( async ( productSlug ) => {
		const products = await window.wp.apiFetch( {
			path: `/wc/store/v1/products?slug=${ encodeURIComponent(
				productSlug
			) }`,
		} );
		return products[ 0 ]?.id;
	}, slug );
}

/**
 * Resolves all terms for a product attribute.
 */
export async function getAllAttributeTerms(
	page: Page,
	attributeSlug: string
): Promise< Array< { id: number; attr_slug: string } > > {
	return page.evaluate( async ( attrSlug ) => {
		const terms = await window.wp.apiFetch( {
			path: `/wc/store/v1/products/attributes/${ attrSlug }/terms?per_page=100`,
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
