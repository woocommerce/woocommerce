/**
 * External dependencies
 */
import { isExperimentalBlocksEnabled } from '@woocommerce/block-settings';
import { productFilterAttribute } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';
import { registerBlockType } from '@wordpress/blocks';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import Save from './save';
import './style.scss';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );
if ( isExperimentalBlocksEnabled() ) {
	const defaultAttribute = getSetting< AttributeSetting >(
		'defaultProductFilterAttribute'
	);

	registerBlockType( metadata, {
		edit: Edit,
		icon: productFilterAttribute,
		attributes: {
			...metadata.attributes,
			attributeId: {
				...metadata.attributes.attributeId,
				default: parseInt( defaultAttribute.attribute_id, 10 ),
			},
		},
		save: Save,
		variations: ATTRIBUTES.map( ( attribute, index ) => {
			return {
				name: `product-filter-attribute-${ attribute.attribute_name }`,
				title: `${ attribute.attribute_label } (Experimental)`,
				description: sprintf(
					// translators: %s is the attribute label.
					__(
						`Enable customers to filter the product collection by selecting one or more %s attributes.`,
						'woocommerce'
					),
					attribute.attribute_label
				),
				attributes: {
					attributeId: parseInt( attribute.attribute_id, 10 ),
				},
				isActive: [ 'attributeId' ],
				isDefault: index === 0,
			};
		} ),
	} );
}
