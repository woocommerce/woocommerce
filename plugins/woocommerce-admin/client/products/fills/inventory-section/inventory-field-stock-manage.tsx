/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useFormContext2, Link } from '@woocommerce/components';
import { TextControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import interpolateComponents from '@automattic/interpolate-components';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';
import { useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { getErrorMessageProps } from '~/products/utils/get-error-message-props';

export const InventoryStockManageField = () => {
	const { control } = useFormContext2< Product >();
	const { field, fieldState } = useController( {
		name: 'stock_quantity',
		control,
		rules: {
			min: {
				value: 0,
				message: __(
					'Stock quantity must be a positive number.',
					'woocommerce'
				),
			},
		},
	} );
	const { field: lowStockCount, fieldState: lowStockAmtFieldState } =
		useController( {
			name: 'low_stock_amount',
			control,
			rules: {
				min: {
					value: 0,
					message: __(
						'Stock quantity must be a positive number.',
						'woocommerce'
					),
				},
			},
		} );
	const notifyLowStockAmount = getAdminSetting( 'notifyLowStockAmount', 2 );

	return (
		<>
			<h4>{ __( 'Product quantity', 'woocommerce' ) }</h4>
			<TextControl
				type="number"
				label={ __( 'Current quantity', 'woocommerce' ) }
				{ ...field }
				{ ...getErrorMessageProps( fieldState ) }
				min={ 0 }
			/>
			<TextControl
				type="number"
				label={ __( 'Email me when quantity reaches', 'woocommerce' ) }
				placeholder={ sprintf(
					// translators: Default quantity to notify merchants of low stock.
					__( '%d (store default)', 'woocommerce' ),
					notifyLowStockAmount
				) }
				{ ...lowStockCount }
				{ ...getErrorMessageProps( lowStockAmtFieldState ) }
				min={ 0 }
			/>
			<span className="woocommerce-product-form__secondary-text">
				{ interpolateComponents( {
					mixedString: __(
						'Make sure to enable notifications in {{link}}store settings{{/link}}.',
						'woocommerce'
					),
					components: {
						link: (
							<Link
								href={ getAdminLink(
									'admin.php?page=wc-settings&tab=products&section=inventory'
								) }
								target="_blank"
								type="wp-admin"
								onClick={ () => {
									recordEvent(
										'product_pricing_list_price_help_tax_settings_click'
									);
								} }
							>
								<></>
							</Link>
						),
						strong: <strong />,
					},
				} ) }
			</span>
		</>
	);
};
