/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductSectionItem as WooProductSectionItem,
	__experimentalWooProductFieldItem as WooProductFieldItem,
	Link,
	useFormContext2,
	CollapsibleContent,
} from '@woocommerce/components';
import { __experimentalProductSectionLayout as ProductSectionLayout } from '@woocommerce/product-editor';
import { Card, CardBody } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';
import { Product } from '@woocommerce/data';
import { useWatch } from 'react-hook-form';

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
import { PLUGIN_ID } from '../constants';

type InventorySectionFillsType = {
	tabId: string;
	basicSectionId: string;
	advancedSectionId: string;
};

export const InventorySectionFills: React.FC< InventorySectionFillsType > = ( {
	tabId,
	basicSectionId,
	advancedSectionId,
} ) => {
	const { control } = useFormContext2< Product >();
	const manageStock = useWatch( { name: 'manage_stock', control } );

	return (
		<>
			<WooProductSectionItem
				id={ basicSectionId }
				tabs={ [ { name: tabId, order: 1 } ] }
				pluginId={ PLUGIN_ID }
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
								section={ basicSectionId }
							/>
							<CollapsibleContent
								toggleText={ __( 'Advanced', 'woocommerce' ) }
							>
								<WooProductFieldItem.Slot
									section={ advancedSectionId }
								/>
							</CollapsibleContent>
						</CardBody>
					</Card>
				</ProductSectionLayout>
			</WooProductSectionItem>
			<WooProductFieldItem
				id="sku"
				sections={ [ { name: basicSectionId, order: 1 } ] }
				pluginId={ PLUGIN_ID }
			>
				<InventorySkuField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id="track-quantity"
				sections={ [ { name: basicSectionId, order: 3 } ] }
				pluginId={ PLUGIN_ID }
			>
				<InventoryTrackQuantityField />
			</WooProductFieldItem>

			{ manageStock ? (
				<WooProductFieldItem
					id="stock-manage"
					sections={ [ { name: basicSectionId, order: 5 } ] }
					pluginId={ PLUGIN_ID }
				>
					<InventoryStockManageField />
				</WooProductFieldItem>
			) : (
				<WooProductFieldItem
					id="stock-manual"
					sections={ [ { name: basicSectionId, order: 5 } ] }
					pluginId={ PLUGIN_ID }
				>
					<InventoryStockManualField />
				</WooProductFieldItem>
			) }

			{ manageStock && (
				<WooProductFieldItem
					id="stock-out"
					sections={ [ { name: advancedSectionId, order: 1 } ] }
					pluginId={ PLUGIN_ID }
				>
					<InventoryStockOutField />
				</WooProductFieldItem>
			) }

			<WooProductFieldItem
				id="stock-limit"
				sections={ [ { name: advancedSectionId, order: 3 } ] }
				pluginId={ PLUGIN_ID }
			>
				<InventoryStockLimitField />
			</WooProductFieldItem>
		</>
	);
};
