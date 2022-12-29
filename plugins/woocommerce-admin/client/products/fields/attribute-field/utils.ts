/**
 * External dependencies
 */
import { ProductAttribute } from '@woocommerce/data';

/**
 * Updates the position of a product attribute from the new items JSX.Element list.
 *
 * @param { JSX.Element[] } items              list of JSX elements coming back from sortable container.
 * @param { Object }        attributeKeyValues key value pair of product attributes.
 */
export function reorderSortableProductAttributePositions(
	items: JSX.Element[],
	attributeKeyValues: Record< number, ProductAttribute >
): ProductAttribute[] {
	return items
		.map( ( { props }, index ): ProductAttribute | undefined => {
			const position = props?.attribute ? props?.attribute.position : NaN;
			if ( ! isNaN( position ) && attributeKeyValues[ position ] ) {
				return {
					...attributeKeyValues[ position ],
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
