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
import { OptionsSection } from '../sections/options-section';
import { ProductVariationsSection } from '../sections/product-variations-section';
import {
	TAB_GENERAL_ID,
	TAB_SHIPPING_ID,
	TAB_INVENTORY_ID,
	TAB_PRICING_ID,
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
				id="tab/general"
				template="tab/general"
				pluginId="core"
				order={ 1 }
				tabProps={ tabPropData.general }
			>
				<WooProductSectionItem.Slot location={ TAB_GENERAL_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id="tab/pricing"
				template="tab/general"
				pluginId="core"
				order={ 3 }
				tabProps={ tabPropData.pricing }
			>
				<WooProductSectionItem.Slot location={ TAB_PRICING_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id="tab/inventory"
				template="tab/general"
				pluginId="core"
				order={ 5 }
				tabProps={ tabPropData.inventory }
			>
				<WooProductSectionItem.Slot location={ TAB_INVENTORY_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id="tab/shipping"
				template="tab/general"
				pluginId="core"
				order={ 7 }
				tabProps={ tabPropData.shipping }
			>
				<WooProductSectionItem.Slot
					location={ TAB_SHIPPING_ID }
					fillProps={ { product } }
				/>
			</WooProductTabItem>
			{ window.wcAdminFeatures[ 'product-variation-management' ] ? (
				<WooProductTabItem
					id="tab/options"
					template="tab/general"
					pluginId="core"
					order={ 9 }
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

registerPlugin( 'wc-admin-product-editor-form-tab-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <Tabs />;
	},
} );
