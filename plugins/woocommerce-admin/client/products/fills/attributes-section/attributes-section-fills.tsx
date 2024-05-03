/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductSectionLayout as ProductSectionLayout,
} from '@woocommerce/product-editor';
import { recordEvent } from '@woocommerce/tracks';

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
					<>
						<span>
							{ __(
								'Add descriptive pieces of information that customers can use to filter and search for this product.',
								'woocommerce'
							) }
						</span>
						<Link
							className="woocommerce-form-section__header-link"
							href="https://woo.com/document/managing-product-taxonomies/#product-attributes"
							target="_blank"
							type="external"
							onClick={ () => {
								recordEvent(
									'learn_more_about_attributes_help'
								);
							} }
						>
							{ __(
								'Learn more about attributes',
								'woocommerce'
							) }
						</Link>
					</>
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
