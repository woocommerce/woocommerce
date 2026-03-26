/**
 * External dependencies
 */
import { Button, ExternalLink, Flex, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from 'react';
import { useInstanceId } from '@wordpress/compose';
import { isEmpty } from 'lodash';
import apiFetch from '@wordpress/api-fetch';
import { speak } from '@wordpress/a11y';
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
			<img
				src={ icon }
				alt={ `${ provider.label } shipping provider logo` }
				key={ providerKey }
			/>
		</div>
	);
};

export default function ShipmentTrackingNumberForm() {
	const [ trackingNumberTemp, setTrackingNumberTemp ] = useState( '' );
	const [ isAmbiguousProvider, setIsAmbiguousProvider ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ editMode, setEditMode ] = useState( false );
	const [ isLoading, setIsLoading ] = useState( false );
	const inputRef = useRef< HTMLInputElement >( null );
	const { order } = useFulfillmentContext();
	const trackingNumberErrorId = useInstanceId(
		ShipmentTrackingNumberForm,
		'tracking-number-error'
	) as string;
	const findingStatusId = useInstanceId(
		ShipmentTrackingNumberForm,
		'finding-status'
	) as string;
	const providerAmbiguityNoticeId = useInstanceId(
		ShipmentTrackingNumberForm,
		'provider-ambiguity-notice'
	) as string;
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
				const errorMessage = __(
					'No information found for this tracking number. Check the number or enter the details manually.',
					'woocommerce'
				);
				setError( errorMessage );
				speak( errorMessage, 'assertive' );
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

			const successMessage = __(
				'Tracking information found successfully.',
				'woocommerce'
			);
			speak( successMessage, 'polite' );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'Tracking number lookup failed:', err );
			const errorMessage = __(
				'Failed to fetch shipment information.',
				'woocommerce'
			);
			setError( errorMessage );
			speak( errorMessage, 'assertive' );
		} finally {
			setIsLoading( false );
		}
	};

	useEffect( () => {
		if ( isEmpty( trackingNumber ) ) {
			setEditMode( true );
		}
	}, [ trackingNumber ] );

	useEffect( () => {
		if ( editMode && inputRef.current ) {
			inputRef.current.focus();
		}
	}, [ editMode ] );

	const handleEditModeToggle = () => {
		setEditMode( true );
		setTrackingNumberTemp( trackingNumber );
	};

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
							ref={ inputRef }
							type="text"
							label={ __( 'Tracking Number', 'woocommerce' ) }
							placeholder={ __(
								'Enter tracking number',
								'woocommerce'
							) }
							value={ trackingNumberTemp }
							onChange={ ( value ) => {
								setTrackingNumberTemp( value );
								if ( error ) {
									setError( null );
								}
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
							aria-invalid={ !! error }
							aria-describedby={
								error ? trackingNumberErrorId : undefined
							}
							autoComplete="off"
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							text={
								isLoading
									? __( 'Finding…', 'woocommerce' )
									: __( 'Find info', 'woocommerce' )
							}
							disabled={
								isLoading ||
								isEmpty( trackingNumberTemp.trim() )
							}
							isBusy={ isLoading }
							onClick={ handleTrackingNumberLookup }
							aria-describedby={
								isLoading ? findingStatusId : undefined
							}
							__next40pxDefaultSize
						/>
						{ isLoading && (
							<span
								id={ findingStatusId }
								className="screen-reader-text"
							>
								{ __(
									'Searching for tracking information…',
									'woocommerce'
								) }
							</span>
						) }
					</div>
				</div>
			) : (
				<>
					<div className="woocommerce-fulfillment-input-container">
						<h4>{ __( 'Tracking Number', 'woocommerce' ) }</h4>
						<div className="woocommerce-fulfillment-input-group space-between">
							<span
								onClick={ handleEditModeToggle }
								role="button"
								tabIndex={ 0 }
								onKeyDown={ ( event ) => {
									if (
										event.key === 'Enter' ||
										event.key === ' '
									) {
										handleEditModeToggle();
									}
								} }
								style={ { cursor: 'pointer' } }
								aria-label={ __(
									'Edit tracking number',
									'woocommerce'
								) }
							>
								{ trackingNumber }
							</span>
							<Button
								size="small"
								aria-label={ __(
									'Edit tracking number',
									'woocommerce'
								) }
								onClick={ handleEditModeToggle }
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
								<p
									className="woocommerce-fulfillment-description"
									id={ providerAmbiguityNoticeId }
								>
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
										speak(
											__(
												'Switched to manual provider selection.',
												'woocommerce'
											),
											'polite'
										);
									} }
									aria-describedby={
										providerAmbiguityNoticeId
									}
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
			{ error && (
				<div id={ trackingNumberErrorId } role="alert">
					<ErrorLabel error={ error } />
				</div>
			) }
		</>
	);
}
