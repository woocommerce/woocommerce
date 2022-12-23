/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { PickupLocation } from '../types';
import StateControl from './state-control';

const Form = ( {
	formRef,
	values,
	setValues,
}: {
	formRef: React.RefObject< HTMLFormElement >;
	values: PickupLocation;
	setValues: React.Dispatch< React.SetStateAction< PickupLocation > >;
} ) => {
	const countries = getSetting< Record< string, string > >( 'countries', [] );
	const states = getSetting< Record< string, Record< string, string > > >(
		'countryStates',
		[]
	);
	const setLocationField =
		( field: keyof PickupLocation ) => ( newValue: string | boolean ) => {
			setValues( ( prevValue: PickupLocation ) => ( {
				...prevValue,
				[ field ]: newValue,
			} ) );
		};

	const setLocationAddressField =
		( field: keyof PickupLocation[ 'address' ] ) =>
		( newValue: string | boolean ) => {
			setValues( ( prevValue ) => ( {
				...prevValue,
				address: {
					...prevValue.address,
					[ field ]: newValue,
				},
			} ) );
		};

	return (
		<form ref={ formRef }>
			<TextControl
				label={ __( 'Location name', 'woo-gutenberg-products-block' ) }
				name={ 'location_name' }
				value={ values.name }
				onChange={ setLocationField( 'name' ) }
				autoComplete="off"
				required={ true }
				onInvalid={ (
					event: React.InvalidEvent< HTMLInputElement >
				) => {
					event.target.setCustomValidity(
						__(
							'A Location title is required',
							'woo-gutenberg-products-block'
						)
					);
				} }
				onInput={ ( event: React.ChangeEvent< HTMLInputElement > ) => {
					event.target.setCustomValidity( '' );
				} }
			/>
			<TextControl
				label={ __( 'Address', 'woo-gutenberg-products-block' ) }
				name={ 'location_address' }
				placeholder={ __( 'Address', 'woo-gutenberg-products-block' ) }
				value={ values.address.address_1 }
				onChange={ setLocationAddressField( 'address_1' ) }
				autoComplete="off"
			/>
			<TextControl
				label={ __( 'City', 'woo-gutenberg-products-block' ) }
				name={ 'location_city' }
				hideLabelFromVision={ true }
				placeholder={ __( 'City', 'woo-gutenberg-products-block' ) }
				value={ values.address.city }
				onChange={ setLocationAddressField( 'city' ) }
				autoComplete="off"
			/>
			<TextControl
				label={ __( 'Postcode / ZIP', 'woo-gutenberg-products-block' ) }
				name={ 'location_postcode' }
				hideLabelFromVision={ true }
				placeholder={ __(
					'Postcode / ZIP',
					'woo-gutenberg-products-block'
				) }
				value={ values.address.postcode }
				onChange={ setLocationAddressField( 'postcode' ) }
				autoComplete="off"
			/>
			<StateControl
				label={ __( 'State', 'woo-gutenberg-products-block' ) }
				name={ 'location_state' }
				hideLabelFromVision={ true }
				placeholder={ __( 'State', 'woo-gutenberg-products-block' ) }
				value={ values.address.state }
				onChange={ setLocationAddressField( 'state' ) }
				autoComplete="off"
				states={ states }
				currentCountry={ values.address.country }
			/>
			<SelectControl
				label={ __( 'Country', 'woo-gutenberg-products-block' ) }
				name={ 'location_country' }
				hideLabelFromVision={ true }
				placeholder={ __( 'Country', 'woo-gutenberg-products-block' ) }
				value={ values.address.country }
				onChange={ ( val: string ) => {
					setLocationAddressField( 'state' )( '' );
					setLocationAddressField( 'country' )( val );
				} }
				autoComplete="off"
				options={ [
					{
						value: '',
						disabled: true,
						label: __( 'Country', 'woo-gutenberg-products-block' ),
					},
					...Object.entries( countries ).map(
						( [ code, country ] ) => ( {
							value: code,
							label: country,
						} )
					),
				] }
			/>
			<TextControl
				label={ __( 'Pickup details', 'woo-gutenberg-products-block' ) }
				name={ 'pickup_details' }
				value={ values.details }
				onChange={ setLocationField( 'details' ) }
				autoComplete="off"
			/>
		</form>
	);
};

export default Form;
