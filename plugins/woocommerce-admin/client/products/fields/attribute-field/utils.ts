/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Returns the attribute key. The key will be the `id` or the `name` when the id is 0.
 *
 * @param { ProductAttribute } attribute product attribute.
 * @return string|number
 */
export function getAttributeKey(
	attribute: ProductAttribute
): number | string {
	return attribute.id !== 0 ? attribute.id : attribute.name;
}

/**
 * Updates the position of a product attribute from the new items JSX.Element list.
 *
 * @param { JSX.Element[] } items              list of JSX elements coming back from sortable container.
 * @param { Object }        attributeKeyValues key value pair of product attributes.
 */
export function reorderSortableProductAttributePositions(
	items: JSX.Element[],
	attributeKeyValues: Record< number | string, ProductAttribute >
): ProductAttribute[] {
	return items
		.map( ( { props }, index ): ProductAttribute | undefined => {
			const key = getAttributeKey( props?.attribute );
			if ( attributeKeyValues[ key ] ) {
				return {
					...attributeKeyValues[ key ],
					position: index,
				};
			}
			return undefined;
		} )
		.filter( ( attr ): attr is ProductAttribute => attr !== undefined );
}

/**
 * Helper function to return the product attribute object. If attribute is a string it will create an object.
 *
 * @param { Object | string } attribute product attribute as string or object.
 */
export function getProductAttributeObject(
	attribute:
		| string
		| Omit< ProductAttribute, 'position' | 'visible' | 'variation' >
): Omit< ProductAttribute, 'position' | 'visible' | 'variation' > {
	return typeof attribute === 'string'
		? {
				id: 0,
				name: attribute,
				options: [],
		  }
		: attribute;
}
