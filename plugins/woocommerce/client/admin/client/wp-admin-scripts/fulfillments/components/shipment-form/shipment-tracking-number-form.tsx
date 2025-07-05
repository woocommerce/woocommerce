/**
 * External dependencies
 */

import { Button, ExternalLink, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import { isEmpty } from 'lodash';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useShipmentFormContext } from '../../context/shipment-form-context';
import ErrorLabel from '../user-interface/error-label';
import { EditIcon } from '../../utils/icons';
import { findShipmentProviderName } from '../../utils/fulfillment-utils';
import ShipmentProviders from '../../data/shipment-providers';
import { useFulfillmentContext } from '../../context/fulfillment-context';

interface TrackingNumberParsingResponse {
	tracking_number_details: {
		tracking_number: string;
		tracking_url: string;
		shipping_provider: string;
	};
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
	} = useShipmentFormContext();

	// Reset error when order changes
	useEffect( () => {
		setError( null );
	}, [ order?.id ] );

	const handleTrackingNumberLookup = async () => {
		setError( null );
		try {
			setIsLoading( true );
			const { tracking_number_details } =
				await apiFetch< TrackingNumberParsingResponse >( {
					path: `/wc/v3/orders/${ order?.id }/fulfillments/lookup?tracking_number=${ trackingNumberTemp }`,
					method: 'GET',
				} );
			if ( ! tracking_number_details.tracking_number ) {
				setError(
					__(
						'No information found for this tracking number. Check the number or enter the details manually.',
						'woocommerce'
					)
				);
				return;
			}
			setTrackingNumber( tracking_number_details.tracking_number );
			setTrackingUrl( tracking_number_details.tracking_url );
			setShipmentProvider( tracking_number_details.shipping_provider );
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
