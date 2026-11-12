/**
 * Internal dependencies
 */
import WooCommerceShippingItem, {
	type ShippingPartnerTrackingProps,
} from './woocommerce-shipping-item';
import ShipStationItem from './shipstation-item';
import PacklinkItem from './packlink-item';
import type { ShippingExtensionId } from './shipping-extensions';

export type ShippingExtensionItemProps = {
	extension: ShippingExtensionId;
	isPluginInstalled: boolean;
	isPluginActive: boolean;
	pluginsBeingSetup: Array< string >;
	onInstallClick: ( slugs: string[] ) => PromiseLike< void >;
	onActivateClick: ( slugs: string[] ) => PromiseLike< void >;
	tracking?: ShippingPartnerTrackingProps;
};

const ITEM_COMPONENTS = {
	'woocommerce-shipping': WooCommerceShippingItem,
	shipstation: ShipStationItem,
	packlink: PacklinkItem,
};

export const ShippingExtensionItem = ( {
	extension,
	...props
}: ShippingExtensionItemProps ) => {
	const Item = ITEM_COMPONENTS[ extension ];

	return Item ? <Item { ...props } /> : null;
};
