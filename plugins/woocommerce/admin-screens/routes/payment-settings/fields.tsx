/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { shouldDisplaySortCode } from '../../../client/admin/client/settings-payments/components/bank-accounts-list/utils';
import { BankAccounts } from './bank-accounts';
import { getAccountCountry, getShippingElements } from './entities';
import type { GatewayId, OfflineSettings } from './entities';

/**
 * Define Core's offline controls on the client without a screen-registration API.
 */
export function getOfflineFields(
	gatewayId: GatewayId,
	data?: OfflineSettings
): Field< OfflineSettings >[] {
	const enabledLabels = {
		bacs: __( 'Enable direct bank transfers', 'woocommerce' ),
		cheque: __( 'Enable check payments', 'woocommerce' ),
		cod: __( 'Enable cash on delivery payments', 'woocommerce' ),
	};
	const shippingOptions =
		data?.settings[ 'enable_for_methods' ]?.options ?? {};
	const fields: Field< OfflineSettings >[] = [
		{
			id: 'enabled',
			type: 'boolean',
			Edit: 'toggle',
			label: enabledLabels[ gatewayId ],
		},
		{
			id: 'title',
			type: 'text',
			label: __( 'Title', 'woocommerce' ),
			description: __(
				'Payment method name that the customer will see during checkout.',
				'woocommerce'
			),
		},
		{
			id: 'description',
			type: 'text',
			Edit: 'textarea',
			label: __( 'Description', 'woocommerce' ),
			description: __(
				'Payment method description that the customer will see during checkout.',
				'woocommerce'
			),
		},
		{
			id: 'instructions',
			type: 'text',
			Edit: 'textarea',
			label: __( 'Instructions', 'woocommerce' ),
			description: __(
				'Instructions that will be added to the thank you page and emails.',
				'woocommerce'
			),
		},
		{
			id: 'enable_for_methods',
			type: 'array',
			label: __( 'Enable for shipping methods', 'woocommerce' ),
			description: __(
				'Select shipping methods for which this payment method is enabled.',
				'woocommerce'
			),
			elements: getShippingElements( shippingOptions ),
		},
		{
			id: 'enable_for_virtual',
			type: 'boolean',
			label: __( 'Accept for virtual orders', 'woocommerce' ),
		},
	];
	if ( gatewayId === 'bacs' ) {
		fields.push( {
			id: 'account_details',
			label: __( 'Bank accounts', 'woocommerce' ),
			Edit: BankAccounts,
			isValid: {
				custom: ( item ) => {
					const accounts = item.account_details ?? [];
					return accounts.every( ( account ) => {
						const country = getAccountCountry(
							account,
							item.default_country
						);
						return (
							account.account_name.trim() !== '' &&
							( ! shouldDisplaySortCode( country ) ||
								account.sort_code.trim() !== '' )
						);
					} )
						? null
						: __(
								'Enter an account name and any required routing number for each bank account.',
								'woocommerce'
							);
				},
			},
		} );
	}
	return fields;
}
