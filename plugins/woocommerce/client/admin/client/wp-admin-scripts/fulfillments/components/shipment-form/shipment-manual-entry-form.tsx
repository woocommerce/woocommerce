/**
 * External dependencies
 */
import { ComboboxControl, TextControl } from '@wordpress/components';
import { ComboboxControlOption } from '@wordpress/components/build-types/combobox-control/types';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useShipmentFormContext } from '../../context/shipment-form-context';
import ShipmentProviders from '../../data/shipment-providers';
import { SearchIcon } from '../../utils/icons';

const ShippingProviderListItem = ( {
	item,
}: {
	item: ComboboxControlOption;
} ) => {
	return (
		<div
			className={ [
				'woocommerce-fulfillment-shipping-provider-list-item',
				'woocommerce-fulfillment-shipping-provider-list-item-' +
					item.value,
			].join( ' ' ) }
		>
			{ item.icon && (
				<div className="woocommerce-fulfillment-shipping-provider-list-item-icon">
					<img src={ item.icon } alt={ item.label } />
				</div>
			) }
			<div className="woocommerce-fulfillment-shipping-provider-list-item-label">
				{ item.label }
			</div>
		</div>
	);
};

export default function ShipmentManualEntryForm() {
	const {
		trackingNumber,
		setTrackingNumber,
		shipmentProvider,
		setShipmentProvider,
		providerName,
		setProviderName,
		trackingUrl,
		setTrackingUrl,
	} = useShipmentFormContext();
	return (
		<>
			<p className="woocommerce-fulfillment-description">
				{ __(
					'Provide the shipment information for this fulfillment.',
					'woocommerce'
				) }
			</p>
			<div className="woocommerce-fulfillment-input-container">
				<div className="woocommerce-fulfillment-input-group">
					<TextControl
						label={ __( 'Tracking Number', 'woocommerce' ) }
						type="text"
						placeholder={ __(
							'Enter tracking number',
							'woocommerce'
						) }
						value={ trackingNumber }
						onChange={ ( value: string ) => {
							setTrackingNumber( value );
						} }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</div>
			</div>
			<div className="woocommerce-fulfillment-input-container">
				<div className="woocommerce-fulfillment-input-group">
					<ComboboxControl
						label={ __( 'Provider', 'woocommerce' ) }
						__experimentalRenderItem={ ( { item } ) => (
							<ShippingProviderListItem item={ item } />
						) }
						allowReset={ false }
						__next40pxDefaultSize
						value={ shipmentProvider }
						options={ ShipmentProviders }
						onChange={ ( value ) => {
							if ( typeof value !== 'string' ) {
								return;
							}
							if ( ! value ) {
								setTrackingUrl( '' );
								return;
							}
							setShipmentProvider( value as string );
							setTrackingUrl(
								(
									window.wcFulfillmentSettings.providers[
										value as string
									]?.url ?? ''
								).replace(
									/__placeholder__/i,
									encodeURIComponent( trackingNumber ?? '' )
								)
							);
						} }
						__nextHasNoMarginBottom
					/>
					<div className="woocommerce-fulfillment-shipment-provider-search-icon">
						<SearchIcon />
					</div>
				</div>
			</div>
			{ shipmentProvider === 'other' && (
				<div className="woocommerce-fulfillment-input-container">
					<div className="woocommerce-fulfillment-input-group">
						<TextControl
							label={ __( 'Provider Name', 'woocommerce' ) }
							type="text"
							placeholder={ __(
								'Enter provider name',
								'woocommerce'
							) }
							value={ providerName }
							onChange={ ( value: string ) => {
								setProviderName( value );
							} }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</div>
				</div>
			) }
			<div className="woocommerce-fulfillment-input-container">
				<div className="woocommerce-fulfillment-input-group">
					<TextControl
						label={ __( 'Tracking URL', 'woocommerce' ) }
						type="text"
						placeholder={ __(
							'Enter tracking URL',
							'woocommerce'
						) }
						value={ trackingUrl }
						onChange={ ( value: string ) => {
							setTrackingUrl( value );
						} }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</div>
			</div>
		</>
	);
}
