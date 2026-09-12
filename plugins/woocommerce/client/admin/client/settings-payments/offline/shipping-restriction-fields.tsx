/**
 * External dependencies
 */
import { TreeSelectControl } from '@woocommerce/components';
import { __, sprintf } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import type { PaymentGateway } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { mapShippingMethodsOptions } from './utils';
import { CheckboxEdit, type OfflineFormValues } from './dataform-controls';

/**
 * Reads the shipping method restriction settings of an offline payment gateway
 * into the form values shape.
 *
 * @param gateway The gateway as returned by the payment gateways store.
 */
export const getShippingRestrictionValues = (
	gateway: PaymentGateway
): OfflineFormValues => ( {
	enable_for_methods: Array.isArray(
		gateway.settings.enable_for_methods?.value
	)
		? gateway.settings.enable_for_methods.value
		: [],
	enable_for_virtual: gateway.settings.enable_for_virtual?.value === 'yes',
} );

/**
 * Serializes the shipping method restriction form values into the settings
 * payload accepted by the payment gateways REST API.
 *
 * @param formValues The current form values.
 */
export const getShippingRestrictionSettings = (
	formValues: OfflineFormValues
): Record< string, string | string[] > => ( {
	enable_for_methods: Array.isArray( formValues.enable_for_methods )
		? formValues.enable_for_methods
		: [],
	enable_for_virtual: formValues.enable_for_virtual ? 'yes' : 'no',
} );

/**
 * Builds the DataForm fields for the "Enable for shipping methods" and
 * "Accept for virtual orders" settings shared by the offline payment gateways.
 *
 * @param gateway    The gateway as returned by the payment gateways store, used for the shipping method options.
 * @param methodName Lowercase payment method name used in the virtual orders description, e.g. "cash on delivery".
 */
export const getShippingRestrictionFields = (
	gateway: PaymentGateway | undefined,
	methodName: string
): Field< OfflineFormValues >[] => {
	const shippingMethodsOptions = gateway?.settings.enable_for_methods?.options
		? mapShippingMethodsOptions(
				gateway.settings.enable_for_methods.options
		  )
		: [];

	return [
		{
			id: 'enable_for_methods',
			label: __( 'Enable for shipping methods', 'woocommerce' ),
			description: __(
				'Select shipping methods for which this payment method is enabled.',
				'woocommerce'
			),
			// Renders the shipping methods multi-select using the options
			// that ship with the gateway.
			Edit: ( { data, field, onChange } ) => {
				const value = field.getValue( { item: data } );
				return (
					<TreeSelectControl
						label={ field.label }
						help={ field.description }
						options={ shippingMethodsOptions }
						value={ Array.isArray( value ) ? value : [] }
						onChange={ ( newValue: string[] ) =>
							onChange( { [ field.id ]: newValue } )
						}
						selectAllLabel={ false }
					/>
				);
			},
		},
		{
			id: 'enable_for_virtual',
			label: __( 'Accept for virtual orders', 'woocommerce' ),
			description: sprintf(
				/* translators: %s: payment method name, e.g. "cash on delivery". */
				__( 'Accept %s if the order is virtual', 'woocommerce' ),
				methodName
			),
			Edit: CheckboxEdit,
		},
	];
};
