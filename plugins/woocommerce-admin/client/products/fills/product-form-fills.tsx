/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	__experimentalWooProductTabItem as WooProductTabItem,
	__experimentalWooProductSectionItem as WooProductSectionItem,
	useFormContext,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ShippingSectionFills } from './shipping-section';
import { PricingSectionFills } from './pricing-section';
import { OptionsSection } from '../sections/options-section';
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
				templates={ [ { name: 'tab/general', order: 1 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.general }
			>
				<WooProductSectionItem.Slot tab={ TAB_GENERAL_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_PRICING_ID }
				templates={ [ { name: 'tab/general', order: 3 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.pricing }
			>
				<WooProductSectionItem.Slot tab={ TAB_PRICING_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_INVENTORY_ID }
				templates={ [ { name: 'tab/general', order: 5 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.inventory }
			>
				<WooProductSectionItem.Slot tab={ TAB_INVENTORY_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ TAB_SHIPPING_ID }
				templates={ [ { name: 'tab/general', order: 7 } ] }
				pluginId={ PLUGIN_ID }
				tabProps={ tabPropData.shipping }
			>
				<WooProductSectionItem.Slot
					tab={ TAB_SHIPPING_ID }
					fillProps={ { product } }
				/>
			</WooProductTabItem>
			{ window.wcAdminFeatures[ 'product-variation-management' ] ? (
				<WooProductTabItem
					id={ TAB_OPTIONS_ID }
					templates={ [ { name: 'tab/general', order: 9 } ] }
					pluginId={ PLUGIN_ID }
					tabProps={ tabPropData.options }
				>
					<>
						<OptionsSection />
						<ProductVariationsSection />
					</>
				</WooProductTabItem>
			) : null }
		</>
	);
};

registerPlugin( 'wc-admin-product-editor-form-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return (
			<>
				<Tabs />
				<ShippingSectionFills
					tabId={ TAB_SHIPPING_ID }
					basicSectionId={ SHIPPING_SECTION_BASIC_ID }
					dimensionsSectionId={ SHIPPING_SECTION_DIMENSIONS_ID }
				/>
				<PricingSectionFills
					tabId={ TAB_PRICING_ID }
					basicSectionId={ PRICING_SECTION_BASIC_ID }
					taxesSectionId={ PRICING_SECTION_TAXES_ID }
				/>
			</>
		);
	},
} );
