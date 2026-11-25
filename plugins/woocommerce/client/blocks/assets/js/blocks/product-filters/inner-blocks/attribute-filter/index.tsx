/**
 * External dependencies
 */
import { productFilterAttribute } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';
import { registerBlockType } from '@wordpress/blocks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import deprecated from './deprecated';
import Edit from './edit';
import Save from './save';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

registerBlockType( metadata, {
	edit: Edit,
	icon: productFilterAttribute,
	save: Save,
	deprecated,
	variations: ATTRIBUTES.map( ( attribute, index ) => {
		return {
			name: `product-filter-attribute-${ attribute.attribute_name }`,
			title: sprintf(
				// translators: %s is the attribute label.
				__( '%s Filter', 'woocommerce' ),
				attribute.attribute_label
			),
			description: sprintf(
				// translators: %s is the attribute label.
				__( 'Let shoppers filter products by %s.', 'woocommerce' ),
				attribute.attribute_label.toLocaleLowerCase()
			),
			attributes: {
				attributeId: parseInt( attribute.attribute_id, 10 ),
			},
			isActive: [ 'attributeId' ],
			isDefault: index === 0,
		};
	} ),
} );
