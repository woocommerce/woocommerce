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
		.map( ( item, index ): ProductAttribute | undefined => {
			const key = item.key ? parseInt( item.key as string, 10 ) : NaN;
			if ( key !== NaN && attributeKeyValues[ key ] ) {
				return {
					...attributeKeyValues[ key ],
					position: index,
				};
			}
			return undefined;
		} )
		.filter( ( attr ): attr is ProductAttribute => attr !== undefined );
}
