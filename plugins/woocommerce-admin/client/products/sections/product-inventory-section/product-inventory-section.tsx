/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	CollapsibleContent,
	__experimentalConditionalWrapper as ConditionalWrapper,
	Link,
	useFormContext,
} from '@woocommerce/components';
import {
	Card,
	CardBody,
	ToggleControl,
	TextControl,
	Tooltip,
} from '@wordpress/components';
import { getAdminLink } from '@woocommerce/settings';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { AdvancedStockSection } from './advanced-stock-section';
import { getCheckboxTracks } from '../utils';
import { getAdminSetting } from '~/utils/admin-settings';
import { ProductSectionLayout } from '../../layout/product-section-layout';
import { ManageStockSection } from './manage-stock-section';
import { ManualStockSection } from './manual-stock-section';

export const ProductInventorySection: React.FC = () => {
	const { getCheckboxControlProps, getInputProps, values } =
		useFormContext< Product >();
	const canManageStock = getAdminSetting( 'manageStock', 'yes' ) === 'yes';

	return (
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
					<TextControl
						label={ __(
							'SKU (Stock Keeping Unit)',
							'woocommerce'
						) }
						{ ...getInputProps( 'sku' ) }
					/>
					<div className="woocommerce-product-form__field">
						<ConditionalWrapper
							condition={ ! canManageStock }
							wrapper={ ( children: JSX.Element ) => (
								<Tooltip
									text={ __(
										'Quantity tracking is disabled for all products. Go to global store settings to change it.',
										'woocommerce'
									) }
									position="top center"
								>
									<div className="woocommerce-product-form__tooltip-disabled-overlay">
										{ children }
									</div>
								</Tooltip>
							) }
						>
							<ToggleControl
								label={ __(
									'Track quantity for this product',
									'woocommerce'
								) }
								{ ...getCheckboxControlProps(
									'manage_stock',
									getCheckboxTracks( 'manage_stock' )
								) }
								// eslint-disable-next-line @typescript-eslint/ban-ts-comment
								// @ts-ignore This prop does exist, but is not typed in @wordpress/components.
								disabled={ ! canManageStock }
							/>
						</ConditionalWrapper>
					</div>
					{ values.manage_stock ? (
						<ManageStockSection />
					) : (
						<ManualStockSection />
					) }
					<CollapsibleContent
						toggleText={ __( 'Advanced', 'woocommerce' ) }
					>
						<AdvancedStockSection />
					</CollapsibleContent>
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
