/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductSectionLayout as ProductSectionLayout,
} from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { AttributesField } from './index';
import { ATTRIBUTES_SECTION_ID, TAB_GENERAL_ID, PLUGIN_ID } from '../constants';

import './attributes-section.scss';

export const AttributesSectionFills = () => (
	<>
		<WooProductSectionItem
			id={ ATTRIBUTES_SECTION_ID }
			tabs={ [ { name: TAB_GENERAL_ID, order: 5 } ] }
			pluginId={ PLUGIN_ID }
		>
			<ProductSectionLayout
				title={ __( 'Attributes', 'woocommerce' ) }
				className="woocommerce-product-attributes-section"
				description={
					<span>
						{ __(
							'Use global attributes to allow shoppers to filter and search for this product. Use custom attributes to provide detailed product information.',
							'woocommerce'
						) }
					</span>
				}
			>
				<WooProductFieldItem.Slot section={ ATTRIBUTES_SECTION_ID } />
			</ProductSectionLayout>
		</WooProductSectionItem>
		<WooProductFieldItem
			id="add"
			sections={ [ { name: ATTRIBUTES_SECTION_ID, order: 1 } ] }
			pluginId={ PLUGIN_ID }
		>
			<AttributesField />
		</WooProductFieldItem>
	</>
);
