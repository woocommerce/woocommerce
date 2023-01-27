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
	const { values } = useFormContext< Product >();

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
				id={ `${ basicSectionId }/sku` }
				sections={ [ { name: basicSectionId, order: 1 } ] }
				pluginId={ PLUGIN_ID }
			>
				<InventorySkuField />
			</WooProductFieldItem>
			<WooProductFieldItem
				id={ `${ basicSectionId }/track-quantity` }
				sections={ [ { name: basicSectionId, order: 3 } ] }
				pluginId={ PLUGIN_ID }
			>
				<InventoryTrackQuantityField />
			</WooProductFieldItem>

			{ values.manage_stock ? (
				<WooProductFieldItem
					id={ `${ basicSectionId }/stock-manage` }
					sections={ [ { name: basicSectionId, order: 5 } ] }
					pluginId={ PLUGIN_ID }
				>
					<InventoryStockManageField />
				</WooProductFieldItem>
			) : (
				<WooProductFieldItem
					id={ `${ basicSectionId }/stock-manual` }
					sections={ [ { name: basicSectionId, order: 5 } ] }
					pluginId={ PLUGIN_ID }
				>
					<InventoryStockManualField />
				</WooProductFieldItem>
			) }

			{ values.manage_stock && (
				<WooProductFieldItem
					id={ `${ advancedSectionId }/stock-out` }
					sections={ [ { name: advancedSectionId, order: 1 } ] }
					pluginId={ PLUGIN_ID }
				>
					<InventoryStockOutField />
				</WooProductFieldItem>
			) }

			<WooProductFieldItem
				id={ `${ advancedSectionId }/stock-limit` }
				sections={ [ { name: advancedSectionId, order: 3 } ] }
				pluginId={ PLUGIN_ID }
			>
				<InventoryStockLimitField />
			</WooProductFieldItem>
		</>
	);
};
