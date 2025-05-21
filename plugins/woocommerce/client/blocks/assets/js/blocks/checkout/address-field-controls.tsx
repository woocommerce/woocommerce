/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RadioControl } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useCheckoutBlockContext } from './context';

export const AddressFieldControls = (): JSX.Element => {
	const { defaultFields } = useCheckoutBlockContext();

	const setFieldEntity = ( field: string, value: string ) => {
		if (
			[ 'phone', 'company', 'address_2' ].includes( field ) &&
			[ 'optional', 'required', 'hidden' ].includes( value )
		) {
			dispatch( coreStore as unknown as string ).editEntityRecord(
				'root',
				'site',
				undefined,
				{
					[ `woocommerce_checkout_${ field }_field` ]: value,
				}
			);
		}
	};

	const requiredOptions = [
		{
			label: __( 'Optional', 'woocommerce' ),
			value: 'false',
		},
		{
			label: __( 'Required', 'woocommerce' ),
			value: 'true',
		},
	];

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Address Fields', 'woocommerce' ) }>
				<p className="wc-block-checkout__controls-text">
					{ __(
						'Show or hide fields in the checkout address forms.',
						'woocommerce'
					) }
				</p>
				<ToggleControl
					label={ __( 'Company', 'woocommerce' ) }
					checked={ ! defaultFields.company.hidden }
					onChange={ () => {
						if ( defaultFields.company.hidden ) {
							setFieldEntity( 'company', 'optional' );
						} else {
							setFieldEntity( 'company', 'hidden' );
						}
					} }
				/>
				{ ! defaultFields.company.hidden && (
					<RadioControl
						selected={
							defaultFields.company.required ? 'true' : 'false'
						}
						options={ requiredOptions }
						onChange={ ( value: string ) => {
							setFieldEntity(
								'company',
								value === 'true' ? 'required' : 'optional'
							);
						} }
						className="components-base-control--nested wc-block-components-require-company-field"
					/>
				) }
				<ToggleControl
					label={ __( 'Address line 2', 'woocommerce' ) }
					checked={ ! defaultFields.address_2.hidden }
					onChange={ () => {
						if ( defaultFields.address_2.hidden ) {
							setFieldEntity( 'address_2', 'optional' );
						} else {
							setFieldEntity( 'address_2', 'hidden' );
						}
					} }
				/>
				{ ! defaultFields.address_2.hidden && (
					<RadioControl
						selected={
							defaultFields.address_2.required ? 'true' : 'false'
						}
						options={ requiredOptions }
						onChange={ ( value: string ) => {
							setFieldEntity(
								'address_2',
								value === 'true' ? 'required' : 'optional'
							);
						} }
						className="components-base-control--nested wc-block-components-require-address_2-field"
					/>
				) }
				<ToggleControl
					label={ __( 'Phone', 'woocommerce' ) }
					checked={ ! defaultFields.phone.hidden }
					onChange={ () => {
						if ( defaultFields.phone.hidden ) {
							setFieldEntity( 'phone', 'optional' );
						} else {
							setFieldEntity( 'phone', 'hidden' );
						}
					} }
				/>
				{ ! defaultFields.phone.hidden && (
					<RadioControl
						selected={
							defaultFields.phone.required ? 'true' : 'false'
						}
						options={ requiredOptions }
						onChange={ ( value: string ) => {
							setFieldEntity(
								'phone',
								value === 'true' ? 'required' : 'optional'
							);
						} }
						className="components-base-control--nested wc-block-components-require-phone-field"
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
};

export default AddressFieldControls;
