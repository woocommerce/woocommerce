/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	__experimentalWooProductTabItem as WooProductTabItem,
	__experimentalWooProductSectionItem as WooProductSectionItem,
} from '@woocommerce/components';
import { PartialProduct } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductVariationDetailsSection } from '../sections/product-variation-details-section';
import { TAB_INVENTORY_ID, TAB_SHIPPING_ID, TAB_PRICING_ID } from './constants';

const tabPropData = {
	general: {
		name: 'general',
		title: __( 'General', 'woocommerce' ),
	},
	pricing: {
		name: 'pricing',
		title: __( 'Pricing', 'woocommerce' ),
	},
	inventory: {
		name: 'inventory',
		title: __( 'Inventory', 'woocommerce' ),
	},
	shipping: {
		name: 'shipping',
		title: __( 'Shipping', 'woocommerce' ),
	},
	options: {
		name: 'options',
		title: __( 'Options', 'woocommerce' ),
	},
};

const Tabs = () => {
	return (
		<>
			<WooProductTabItem
				id="tab/general/variation"
				template="tab/variation"
				pluginId="core"
				order={ 1 }
				tabProps={ tabPropData.general }
			>
				<ProductVariationDetailsSection />
			</WooProductTabItem>
			<WooProductTabItem
				id="tab/pricing"
				template="tab/variation"
				pluginId="core"
				order={ 3 }
				tabProps={ tabPropData.pricing }
			>
				<WooProductSectionItem.Slot location={ TAB_PRICING_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id="tab/inventory"
				template="tab/variation"
				pluginId="core"
				order={ 5 }
				tabProps={ tabPropData.inventory }
			>
				<WooProductSectionItem.Slot location={ TAB_INVENTORY_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id="tab/shipping"
				template="tab/variation"
				pluginId="core"
				order={ 7 }
				tabProps={ tabPropData.shipping }
			>
				{ ( { product }: { product: PartialProduct } ) => (
					<WooProductSectionItem.Slot
						location={ TAB_SHIPPING_ID }
						fillProps={ { product } }
					/>
				) }
			</WooProductTabItem>
		</>
	);
};

/**
 * Preloading product form data, as product pages are waiting on this to be resolved.
 * The above Form component won't get rendered until the getProductForm is resolved.
 */
registerPlugin( 'wc-admin-product-editor-form-variation-tab-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <Tabs />;
	},
} );
