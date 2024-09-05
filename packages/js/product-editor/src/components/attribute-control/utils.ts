/**
 * External dependencies
 */
import type { ProductProductAttribute } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { EnhancedProductAttribute } from '../../hooks/use-product-attributes';

/**
 * Returns the attribute key. The key will be the `id` or the `name` when the id is 0.
 *
 * @param { ProductProductAttribute } attribute product attribute.
 * @return string|number
 */
export function getAttributeKey(
	attribute: ProductProductAttribute
): number | string {
	return attribute.id !== 0 ? attribute.id : attribute.name;
}

/**
 * Get an attribute ID that works universally across global and local attributes.
 *
 * @param attribute Product attribute.
 * @return string
 */
export const getAttributeId = ( attribute: ProductProductAttribute ) =>
	`${ attribute.id }-${ attribute.name }`;

/**
 * Updates the position of a product attribute from the new items list.
 *
 * @param { Object } items              key value pair of list items positions.
 * @param { Object } attributeKeyValues key value pair of product attributes.
 */
export function reorderSortableProductAttributePositions(
	items: Record< number | string, number >,
	attributeKeyValues: Record< number | string, ProductProductAttribute >
): ProductProductAttribute[] {
	return Object.keys( attributeKeyValues ).map(
		( attributeKey: number | string ): ProductProductAttribute => {
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
 * Checks if the given attribute has
 * either terms (global attributes) or options (local attributes).
 *
 * @param {EnhancedProductAttribute} attribute - The attribute to check.
 * @return {boolean} True if the attribute has terms or options, false otherwise.
 */
export const hasTermsOrOptions = (
	attribute: EnhancedProductAttribute | null
): boolean => !! ( attribute?.terms?.length || attribute?.options?.length );

/**
 * Checks if the given attribute is filled out,
 * meaning it has a name and either terms or options.
 *
 * @param {EnhancedProductAttribute | null} attribute - The attribute to check.
 * @return {attribute is EnhancedProductAttribute} - True if the attribute is filled out, otherwise false.
 */
export const isAttributeFilledOut = (
	attribute: EnhancedProductAttribute | null
): attribute is EnhancedProductAttribute =>
	!! attribute?.name.length && hasTermsOrOptions( attribute );
