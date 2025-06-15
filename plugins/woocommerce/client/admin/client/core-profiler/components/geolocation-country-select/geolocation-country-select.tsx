/**
 * External dependencies
 */
import {
	useEffect,
	useState,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';
import { SelectControl } from '@woocommerce/components';
import { Icon, chevronDown } from '@wordpress/icons';
import { findCountryOption, getCountry } from '@woocommerce/onboarding';
import { GeolocationResponse } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { CountryStateOption } from '../../services/country';

type Props = {
	countries: CountryStateOption[];
	geolocatedLocation?: GeolocationResponse | null;
	initialValue: CountryStateOption;
	label: string;
	placeholder: string;
	onChange: ( country: CountryStateOption ) => void;
	onGeolocationOverruledChange?: ( overruled: boolean ) => void;
};

export const GeolocationCountrySelect = ( {
	countries,
	geolocatedLocation,
	initialValue,
	label,
	placeholder,
	onChange,
	onGeolocationOverruledChange,
}: Props ) => {
	const [ selectedCountry, setSelectedCountry ] =
		useState< CountryStateOption >( initialValue );

	const [ geolocationMatch, setGeolocationMatch ] =
		useState< CountryStateOption >( { key: '', label: '' } );

	const [ dismissedNotice, setDismissedNotice ] = useState( false );

	useEffect( () => {
		setSelectedCountry( initialValue );
	}, [ initialValue ] );

	useEffect( () => {
		if ( geolocatedLocation ) {
			const match = findCountryOption( countries, geolocatedLocation );
			if ( match ) {
				setGeolocationMatch( match );
				if ( ! initialValue?.key ) {
					setSelectedCountry( match );
					onChange( match );
				}
			}
		}
	}, [ countries, geolocatedLocation, initialValue?.key ] );

	const [ geolocationOverruled, setGeolocationOverruled ] = useState( false );

	useEffect( () => {
		const overruled = Boolean(
			geolocatedLocation &&
				getCountry( selectedCountry?.key ) !==
					getCountry( geolocationMatch?.key )
		);

		setGeolocationOverruled( overruled );
		onGeolocationOverruledChange?.( overruled );
	}, [ selectedCountry, geolocationMatch, geolocatedLocation ] );

	return (
		<div className="woocommerce-geolocation-country-select">
			<SelectControl
				className="woocommerce-profiler-select-control__country"
				instanceId={ 2 }
				placeholder={ placeholder }
				label={ selectedCountry.key === '' ? label : '' }
				ignoreDiacritics={ true }
				getSearchExpression={ ( query: string ) => {
					return new RegExp( `(^${ query }| — (${ query }))`, 'i' );
				} }
				options={ countries }
				help={ <Icon icon={ chevronDown } /> }
				onChange={ ( results ) => {
					if ( Array.isArray( results ) && results.length ) {
						onChange?.( results[ 0 ] as CountryStateOption );
					}
				} }
				selected={ selectedCountry ? [ selectedCountry ] : [] }
				showAllOnFocus
				isSearchable
				virtualScroll={ true }
				virtualItemHeight={ 40 }
				virtualListHeight={ 40 * 9 }
			/>

			<div className="woocommerce-profiler-select-control__country-spacer" />

			{ geolocationOverruled && ! dismissedNotice && (
				<Notice
					className="woocommerce-profiler-geolocation-notice"
					onRemove={ () => setDismissedNotice( true ) }
					status="warning"
				>
					<p>
						{ createInterpolateElement(
							__(
								'It looks like you’re located in <geolocatedCountry></geolocatedCountry>. Are you sure you want to create a store in <selectedCountry></selectedCountry>?',
								'woocommerce'
							),
							{
								geolocatedCountry: (
									<Button
										className="geolocation-notice-geolocated-country"
										variant="link"
										onClick={ () => {
											setSelectedCountry(
												geolocationMatch
											);
											onChange( geolocationMatch );
										} }
									>
										{ geolocatedLocation?.country_long }
									</Button>
								),
								selectedCountry: (
									<span className="geolocation-notice-selected-country">
										{ selectedCountry.label }
									</span>
								),
							}
						) }
					</p>
					<p>
						{ __(
							'Setting up your store in the wrong country may lead to the following issues:',
							'woocommerce'
						) }
					</p>
					<ul className="woocommerce-profiler-geolocation-notice__list">
						<li>
							{ __( 'Tax and duty obligations', 'woocommerce' ) }
						</li>
						<li>{ __( 'Payment issues', 'woocommerce' ) }</li>
						<li>{ __( 'Shipping issues', 'woocommerce' ) }</li>
					</ul>
				</Notice>
			) }
		</div>
	);
};
