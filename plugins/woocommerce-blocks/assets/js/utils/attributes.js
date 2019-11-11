/**
 * External dependencies
 */
import { find } from 'lodash';
import { ATTRIBUTES } from '@woocommerce/block-settings';

/**
 * Get the ID of the first image attached to a product (the featured image).
 *
 * @param {number} attributeId The attribute ID.
 * @return {string} The taxonomy name.
 */
export function getTaxonomyFromAttributeId( attributeId ) {
	if ( ! attributeId ) {
		return null;
	}

	const productAttribute = find( ATTRIBUTES, [
		'attribute_id',
		attributeId.toString(),
	] );

	return productAttribute.attribute_name
		? 'pa_' + productAttribute.attribute_name
		: null;
}
