/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	__experimentalProductSectionLayout as ProductSectionLayout,
	Link,
	useFormContext,
	CollapsibleContent,
} from '@woocommerce/components';
import { Card, CardBody } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	InventorySkuField,
	InventoryTrackQuantityField,
	InventoryStockManualField,
	InventoryStockManageField,
	InventoryStockLimitField,
	InventoryStockOutField,
} from './index';
import {
	INVENTORY_SECTION_ID,
	INVENTORY_SECTION_ADVANCED_ID,
	TAB_INVENTORY_ID,
	PLUGIN_ID,
} from '../constants';

const InventorySection = () => {
	const { values } = useFormContext< Product >();

	return (
		<>
			<WooProductSectionItem
				id={ INVENTORY_SECTION_ID }
				location={ TAB_INVENTORY_ID }
				pluginId={ PLUGIN_ID }
				order={ 1 }
			>
				<ProductSectionLayout
					title={ __( 'Inventory', 'woocommerce' ) }
					description={
						<>
							<span>
								{ __(
									'Set up and manage inventory for this product, including status and available quantity.',
									'woocommerce'
								) }
							</span>
							<Link
								href={ getAdminLink(
									'admin.php?page=wc-settings&tab=products&section=inventory'
								) }
								target="_blank"
								type="wp-admin"
								onClick={ () => {
									recordEvent( 'add_product_inventory_help' );
								} }
								className="woocommerce-form-section__header-link"
							>
								{ __(
									'Manage global inventory settings',
									'woocommerce'
								) }
							</Link>
						</>
					}
				>
					<Card>
						<CardBody>
							<WooProductFieldItem.Slot
								section={ INVENTORY_SECTION_ID }
							/>
							<CollapsibleContent
								toggleText={ __( 'Advanced', 'woocommerce' ) }
							>
								<WooProductFieldItem.Slot
									section={ INVENTORY_SECTION_ADVANCED_ID }
								/>
							</CollapsibleContent>
						</CardBody>
					</Card>
				</ProductSectionLayout>
			</WooProductSectionItem>
			<WooProductFieldItem
				id="inventory/sku"
				section={ INVENTORY_SECTION_ID }
				pluginId={ PLUGIN_ID }
				order={ 1 }
			>
				<InventorySkuField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="inventory/track-quantity"
				section={ INVENTORY_SECTION_ID }
				pluginId={ PLUGIN_ID }
				order={ 3 }
			>
				<InventoryTrackQuantityField />
			</WooProductFieldItem>

			{ values.manage_stock ? (
				<WooProductFieldItem
					id="inventory/stock-manage"
					section={ INVENTORY_SECTION_ID }
					pluginId={ PLUGIN_ID }
					order={ 5 }
				>
					<InventoryStockManageField />
				</WooProductFieldItem>
			) : (
				<WooProductFieldItem
					id="inventory/stock-manual"
					section={ INVENTORY_SECTION_ID }
					pluginId={ PLUGIN_ID }
					order={ 5 }
				>
					<InventoryStockManualField />
				</WooProductFieldItem>
			) }

			{ values.manage_stock && (
				<WooProductFieldItem
					id="inventory/advanced/stock-out"
					section={ INVENTORY_SECTION_ADVANCED_ID }
					pluginId={ PLUGIN_ID }
					order={ 1 }
				>
					<InventoryStockOutField />
				</WooProductFieldItem>
			) }

			<WooProductFieldItem
				id="inventory/advanced/stock-limit"
				section={ INVENTORY_SECTION_ADVANCED_ID }
				pluginId={ PLUGIN_ID }
				order={ 3 }
			>
				<InventoryStockLimitField />
			</WooProductFieldItem>
		</>
	);
};

registerPlugin( 'wc-admin-product-editor-inventory-section', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => <InventorySection />,
} );
