/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { ChangeEvent } from 'react';
import { getAdminLink } from '@woocommerce/settings';
import { Link, useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useInstanceId } from '@wordpress/compose';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore This component does exist.
	__experimentalHStack as HStack,
	BaseControl,
	Card,
	CardBody,
	FormToggle,
	ToggleControl,
	TextControl,
	Tooltip,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../utils';
import { getAdminSetting } from '~/utils/admin-settings';
import { ProductSectionLayout } from '../../layout/product-section-layout';
import { ManageStockSection } from './manage-stock-section';
import { ManualStockSection } from './manual-stock-section';

export const ProductInventorySection: React.FC = () => {
	const { getCheckboxControlProps, getInputProps, values } =
		useFormContext< Product >();
	const canManageStock = getAdminSetting( 'manageStock', 'yes' ) === 'yes';
	const toggleInstanceId = useInstanceId( ToggleControl );
	const toggleId = `inspector-toggle-control-${ toggleInstanceId }`;
	const toggleInputProps = getCheckboxProps(
		getInputProps( 'manage_stock' )
	);

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
						placeholder={ __(
							'washed-oxford-button-down-shirt',
							'woocommerce'
						) }
						{ ...getTextControlProps( getInputProps( 'sku' ) ) }
					/>
					<BaseControl
						id={ toggleId }
						className={ 'components-toggle-control' }
					>
						<HStack justify="flex-start" spacing={ 3 }>
							<Tooltip
								text={ __(
									'Quantity tracking is disabled for all products. Go to global store settings to change it.',
									'woocommerce'
								) }
								position="top center"
							>
								<div className="components-tooltip__overlay">
									<FormToggle
										id={ toggleId }
										disabled={ ! canManageStock }
										{ ...toggleInputProps }
										onChange={ (
											event: ChangeEvent< HTMLInputElement >
										) => {
											toggleInputProps.onChange(
												event.target.checked
											);
										} }
									/>
								</div>
							</Tooltip>
							<label
								htmlFor={ toggleId }
								className="components-toggle-control__label"
							>
								{ __(
									'Track quantity for this product',
									'woocommerce'
>>>>>>> 028df8e9f6 (Disable product inventory toggle when inventory management is disabled)
								) }
							</label>
						</HStack>
					</BaseControl>

					{ values.manage_stock ? (
						<ManageStockSection />
					) : (
						<ManualStockSection />
					) }
				</CardBody>
			</Card>
		</ProductSectionLayout>
	);
};
