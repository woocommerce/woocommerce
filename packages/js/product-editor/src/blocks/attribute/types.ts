/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';
import { ProductAttribute } from '@woocommerce/data';

export interface AttributeBlockAttributes extends BlockAttributes {
	entityAttribute: ProductAttribute;
}
