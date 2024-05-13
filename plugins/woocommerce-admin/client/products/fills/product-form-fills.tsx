/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	__experimentalWooProductTabItem as WooProductTabItem,
	__experimentalWooProductSectionItem as WooProductSectionItem,
} from '@woocommerce/product-editor';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DetailsSectionFills } from './details-section';
import { ShippingSectionFills } from './shipping-section';
import { PricingSectionFills } from './pricing-section';
import { InventorySectionFills } from './inventory-section';
import { AttributesSectionFills } from './attributes-section';
import { ImagesSectionFills } from './images-section';
import { ProductVariationsSection } from '../sections/product-variations-section';
import {
	TAB_GENERAL_ID,
	TAB_SHIPPING_ID,
	TAB_INVENTORY_ID,
	TAB_PRICING_ID,
	TAB_OPTIONS_ID,
	SHIPPING_SECTION_BASIC_ID,
	SHIPPING_SECTION_DIMENSIONS_ID,
	PRICING_SECTION_BASIC_ID,
	PRICING_SECTION_TAXES_ID,
	PRICING_SECTION_TAXES_ADVANCED_ID,
	INVENTORY_SECTION_ID,
	INVENTORY_SECTION_ADVANCED_ID,
	PLUGIN_ID,
} from './constants';

const Tabs = () => {
	const { values: product } = useFormContext< Product >();
	const tabPropData = useMemo(
		() => ( {
			general: {
				name: 'general',
				title: __( 'General', 'woocommerce' ),
			},
			pricing: {
				name: 'pricing',
				title: __( 'Pricing', 'woocommerce' ),
				disabled: !! product?.variations?.length,
			},
			inventory: {
				name: 'inventory',
				title: __( 'Inventory', 'woocommerce' ),
				disabled: !! product?.variations?.length,
			},
			shipping: {
				name: 'shipping',
				title: __( 'Shipping', 'woocommerce' ),
				disabled: !! product?.variations?.length,
			},
			options: {
				name: 'options',
				title: __( 'Options', 'woocommerce' ),
			},
		} ),
		[ product.variations ]
	);
	return (
		<>
			<WooProductTabItem
				id={ TAB_GENERAL_ID }
				templates={ [ { name: TAB_GENERAL_ID, order: 1 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.general }
			>
				<WooProductSectionItem.Slot tab={ TAB_GENERAL_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_PRICING_ID }
				templates={ [ { name: TAB_GENERAL_ID, order: 3 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.pricing }
			>
				<WooProductSectionItem.Slot tab={ TAB_PRICING_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_INVENTORY_ID }
				templates={ [ { name: TAB_GENERAL_ID, order: 5 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.inventory }
			>
				<WooProductSectionItem.Slot tab={ TAB_INVENTORY_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_SHIPPING_ID }
				templates={ [ { name: TAB_GENERAL_ID, order: 7 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.shipping }
			>
				<WooProductSectionItem.Slot
					tab={ TAB_SHIPPING_ID }
					fillProps={ { product } }
				/>
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_OPTIONS_ID }
				templates={ [ { name: TAB_GENERAL_ID, order: 9 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.options }
			>
				<>
					<ProductVariationsSection />
				</>
			</WooProductTabItem>
		</>
	);
};

registerPlugin( 'wc-admin-product-editor-form-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => (
		<>
			<Tabs />
			<DetailsSectionFills />
			<ImagesSectionFills />
			<AttributesSectionFills />
			<ShippingSectionFills
				tabId={ TAB_SHIPPING_ID }
				basicSectionId={ SHIPPING_SECTION_BASIC_ID }
				dimensionsSectionId={ SHIPPING_SECTION_DIMENSIONS_ID }
			/>
			<PricingSectionFills
				tabId={ TAB_PRICING_ID }
				basicSectionId={ PRICING_SECTION_BASIC_ID }
				taxesSectionId={ PRICING_SECTION_TAXES_ID }
				taxesAdvancedSectionId={ PRICING_SECTION_TAXES_ADVANCED_ID }
			/>
			<InventorySectionFills
				tabId={ TAB_INVENTORY_ID }
				basicSectionId={ INVENTORY_SECTION_ID }
				advancedSectionId={ INVENTORY_SECTION_ADVANCED_ID }
			/>
		</>
	),
} );
