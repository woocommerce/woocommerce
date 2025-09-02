/**
 * External dependencies
 */
import { Button, ExternalLink, Flex, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import { isEmpty } from 'lodash';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { useShipmentFormContext } from '../../context/shipment-form-context';
import ErrorLabel from '../user-interface/error-label';
import { EditIcon } from '../../utils/icons';
import { findShipmentProviderName } from '../../utils/fulfillment-utils';
import ShipmentProviders from '../../data/shipment-providers';
import { useFulfillmentContext } from '../../context/fulfillment-context';
import { SHIPMENT_OPTION_MANUAL_ENTRY } from '../../data/constants';

interface TrackingNumberParsingPossibility {
	url: string;
	ambiguity_score: number;
}

interface TrackingNumberParsingResponse {
	tracking_number: string;
	tracking_url: string;
	shipping_provider: string;
	possibilities?: Record< string, TrackingNumberParsingPossibility >;
}

const ShipmentProviderIcon = ( { providerKey }: { providerKey: string } ) => {
	const provider = ShipmentProviders.find( ( p ) => p.value === providerKey );
	const icon = provider?.icon;
	if ( ! provider || ! icon ) {
		return null;
	}

	return (
		<div className="woocommerce-fulfillment-shipment-provider-icon">
			<img src={ icon } alt={ provider.label } key={ providerKey } />
		</div>
	);
};

export default function ShipmentTrackingNumberForm() {
	const [ trackingNumberTemp, setTrackingNumberTemp ] = useState( '' );
	const [ isAmbiguousProvider, setIsAmbiguousProvider ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ editMode, setEditMode ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const { order } = useFulfillmentContext();
	const {
		trackingNumber,
		setTrackingNumber,
		trackingUrl,
		setTrackingUrl,
		setProviderName,
		shipmentProvider,
		setShipmentProvider,
		setSelectedOption,
	} = useShipmentFormContext();

	// Reset error when order changes
	useEffect( () => {
		setError( null );
	}, [ order?.id ] );

	const handleTrackingNumberLookup = async () => {
		setError( null );
		try {
			setIsLoading( true );
			const tracking_number_response =
				await apiFetch< TrackingNumberParsingResponse >( {
					path: addQueryArgs(
						`/wc/v3/orders/${ order?.id }/fulfillments/lookup`,
						{
							tracking_number: trackingNumberTemp.trim(),
						}
					),
					method: 'GET',
				} );
			if ( ! tracking_number_response.tracking_number ) {
				setError(
					__(
						'No information found for this tracking number. Check the number or enter the details manually.',
						'woocommerce'
					)
				);
				return;
			}

			// Reset the ambiguous provider state when a new tracking number is looked up
			setIsAmbiguousProvider( false );

			if (
				tracking_number_response.possibilities &&
				Object.keys( tracking_number_response.possibilities ).length > 1
			) {
				const possibilities = Object.values(
					tracking_number_response.possibilities
				);
				// If one possibility has an ambiguity score of 85 or more, we assume it's a clear match. (test  123456789012:US)
				// If all possibilities have an ambiguity score less than 85, show the ambiguous provider message. (test 1234567890123456:US)
				// If multiple possibilities have ambiguity scores of 85 or more, we still consider it ambiguous. (test AB123456789US:US)
				const hasAmbiguousPossibilities =
					possibilities.every(
						( possibility ) => possibility.ambiguity_score < 85
					) ||
					possibilities.filter(
						( possibility ) => possibility.ambiguity_score >= 85
					).length > 1;
				if ( hasAmbiguousPossibilities ) {
					setIsAmbiguousProvider( true );
				}
			}

			setTrackingNumber( tracking_number_response.tracking_number );
			setTrackingUrl( tracking_number_response.tracking_url );
			setShipmentProvider( tracking_number_response.shipping_provider );
			setProviderName( '' );
			setEditMode( false );
		} catch ( err ) {
			setError(
				__( 'Failed to fetch shipment information.', 'woocommerce' )
			);
		} finally {
			setIsLoading( false );
		}
	};

	useEffect( () => {
		if ( isEmpty( trackingNumber ) ) {
			setEditMode( true );
		}
	}, [ trackingNumber ] );

	return (
		<>
			<p className="woocommerce-fulfillment-description">
				{ __(
					'Provide the shipment tracking number to find the shipment provider and tracking URL.',
					'woocommerce'
				) }
			</p>
			{ editMode ? (
				<div className="woocommerce-fulfillment-input-container">
					<div className="woocommerce-fulfillment-input-group">
						<TextControl
							type="text"
							label={ __( 'Tracking Number', 'woocommerce' ) }
							placeholder={ __(
								'Enter tracking number',
								'woocommerce'
							) }
							value={ trackingNumberTemp }
							onChange={ ( value ) => {
								setTrackingNumberTemp( value );
							} }
							onKeyDown={ ( event ) => {
								if (
									event.key === 'Enter' &&
									! isLoading &&
									! isEmpty( trackingNumberTemp.trim() )
								) {
									handleTrackingNumberLookup();
								}
							} }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							text="Find info"
							disabled={
								isLoading ||
								isEmpty( trackingNumberTemp.trim() )
							}
							isBusy={ isLoading }
							onClick={ handleTrackingNumberLookup }
							__next40pxDefaultSize
						/>
					</div>
				</div>
			) : (
				<>
					<div className="woocommerce-fulfillment-input-container">
						<h4>{ __( 'Tracking Number', 'woocommerce' ) }</h4>
						<div className="woocommerce-fulfillment-input-group space-between">
							<span
								onClick={ () => {
									setEditMode( true );
									setTrackingNumberTemp( trackingNumber );
								} }
								role="button"
								tabIndex={ 0 }
								onKeyDown={ ( event ) => {
									if (
										event.key === 'Enter' ||
										event.key === ' '
									) {
										setEditMode( true );
										setTrackingNumberTemp( trackingNumber );
									}
								} }
								style={ { cursor: 'pointer' } }
							>
								{ trackingNumber }
							</span>
							<Button
								size="small"
								onClick={ () => {
									setEditMode( true );
									setTrackingNumberTemp( trackingNumber );
								} }
							>
								<EditIcon />
							</Button>
						</div>
					</div>
					<div className="woocommerce-fulfillment-input-container">
						<h4>{ __( 'Provider', 'woocommerce' ) }</h4>
						<div className="woocommerce-fulfillment-input-group">
							<div>
								<ShipmentProviderIcon
									providerKey={ shipmentProvider }
								/>
								<span>
									{ findShipmentProviderName(
										shipmentProvider
									) }
								</span>
							</div>
						</div>
						{ isAmbiguousProvider && (
							<Flex direction={ 'column' } gap={ 0 }>
								<p className="woocommerce-fulfillment-description">
									{ __(
										'Not your provider?',
										'woocommerce'
									) }
								</p>
								<Button
									variant="link"
									size="small"
									className="woocommerce-fulfillment-description-button"
									onClick={ () => {
										setSelectedOption(
											SHIPMENT_OPTION_MANUAL_ENTRY
										);
									} }
								>
									{ __(
										'Select your provider manually',
										'woocommerce'
									) }
								</Button>
							</Flex>
						) }
					</div>
					<div className="woocommerce-fulfillment-input-container">
						<h4>{ __( 'Tracking URL', 'woocommerce' ) }</h4>
						<div className="woocommerce-fulfillment-input-group">
							<ExternalLink
								href={ trackingUrl }
								style={ {
									width: '100%',
									textOverflow: 'ellipsis',
									whiteSpace: 'nowrap',
									overflow: 'hidden',
								} }
							>
								{ trackingUrl }
							</ExternalLink>
						</div>
					</div>
				</>
			) }
			{ error && <ErrorLabel error={ error } /> }
		</>
	);
}
