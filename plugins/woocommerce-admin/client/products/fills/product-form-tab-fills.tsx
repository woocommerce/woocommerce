/**
 * External dependencies
 */
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
} from './fills/constants';

const Tabs = () => {
	const { values: product } = useFormContext< Product >();
	const tabPropData = useMemo(
		() => ( {
			general: {
				name: 'general',
				title: 'General',
			},
			pricing: {
				name: 'pricing',
				title: 'Pricing',
				disabled: !! product?.variations?.length,
			},
			inventory: {
				name: 'inventory',
				title: 'Inventory',
				disabled: !! product?.variations?.length,
			},
			shipping: {
				name: 'shipping',
				title: 'Shipping',
				disabled: !! product?.variations?.length,
			},
			options: {
				name: 'options',
				title: 'Options',
			},
		} ),
		[]
	);
	return (
		<>
			<WooProductTabItem
				id={ 'new-tab-id' }
				location="tab/general"
				pluginId="core"
				order={ 1 }
				tabProps={ tabPropData.general }
			>
				<WooProductSectionItem.Slot location={ TAB_GENERAL_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ 'tab/pricing' }
				location="tab/general"
				pluginId="core"
				order={ 3 }
				tabProps={ tabPropData.pricing }
			>
				<WooProductSectionItem.Slot location={ TAB_PRICING_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ 'tab/inventory' }
				location="tab/general"
				pluginId="core"
				order={ 5 }
				tabProps={ tabPropData.inventory }
			>
				<WooProductSectionItem.Slot location={ TAB_INVENTORY_ID } />
			</WooProductTabItem>
			<WooProductTabItem
				id={ 'tab/shipping' }
				location="tab/general"
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
					id={ 'tab/options' }
					location="tab/general"
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

/**
 * Preloading product form data, as product pages are waiting on this to be resolved.
 * The above Form component won't get rendered until the getProductForm is resolved.
 */
registerPlugin( 'wc-admin-product-editor-form-tab-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <Tabs />;
	},
} );
