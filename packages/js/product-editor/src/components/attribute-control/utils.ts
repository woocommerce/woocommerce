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
 * Get an attribute ID that works universally across global and local attributes.
 *
 * @param attribute Product attribute.
 * @return string
 */
export const getAttributeId = ( attribute: ProductAttribute ) =>
	`${ attribute.id }-${ attribute.name }`;

/**
 * Updates the position of a product attribute from the new items list.
 *
 * @param { Object } items              key value pair of list items positions.
 * @param { Object } attributeKeyValues key value pair of product attributes.
 */
export function reorderSortableProductAttributePositions(
	items: Record< number | string, number >,
	attributeKeyValues: Record< number | string, ProductAttribute >
): ProductAttribute[] {
	return Object.keys( attributeKeyValues ).map(
		( attributeKey: number | string ): ProductAttribute => {
			if ( ! isNaN( items[ attributeKey ] ) ) {
				return {
					...attributeKeyValues[ attributeKey ],
					position: items[ attributeKey ],
				};
			}
			return {
				...attributeKeyValues[ attributeKey ],
			};
		}
	);
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
				slug: attribute,
				options: [],
		  }
		: attribute;
}
