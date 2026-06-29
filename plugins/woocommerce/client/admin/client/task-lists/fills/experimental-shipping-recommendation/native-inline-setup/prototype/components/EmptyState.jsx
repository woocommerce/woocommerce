/**
 * External dependencies
 */
import { useState } from 'react';
import { Button } from '@wordpress/components';
import { Icon, chevronDownSmall, moreVertical } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import fedexLogo from '../assets/fedex-icon.jpeg';
import shippoLogo from '../assets/shippo-symbol.png';
import shipstationLogo from '../assets/shipstation-icon.png';
import wooShippingLogo from '../assets/woo-shipping-logo.svg';

const shippingPartners = [
	{
		id: 'woocommerce-shipping',
		name: 'Woo Shipping',
		description: 'Live carrier rates, discounted labels, and tracking.',
		services: [ 'USPS', 'UPS', 'Fedex' ],
		action: 'Set up',
		isPrimary: true,
		logo: wooShippingLogo,
		logoAlt: '',
	},
	{
		id: 'shippo',
		name: 'Shippo',
		description:
			'Multi-carrier rates, labels, and tracking for stores that already use Shippo.',
		action: 'Install',
		logo: shippoLogo,
		logoAlt: '',
	},
	{
		id: 'shipstation',
		name: 'ShipStation',
		description: 'Batch label workflows across stores and sales channels.',
		action: 'Install',
		logo: shipstationLogo,
		logoAlt: '',
	},
	{
		id: 'fedex',
		name: 'FedEx',
		description:
			'Use an existing FedEx account for negotiated rates and label printing.',
		action: 'Install',
		logo: fedexLogo,
		logoAlt: '',
	},
];

export function ShippingProviderChoice( {
	isWooShippingConnected = false,
	isSettingsSurface = false,
	onStartSetup,
	onManageWooShipping,
	onBack,
} ) {
	const primaryPartner = shippingPartners.find(
		( partner ) => partner.isPrimary
	);
	const otherPartners = shippingPartners.filter(
		( partner ) => ! partner.isPrimary
	);
	const [ selectedPartner, setSelectedPartner ] = useState( null );
	const primaryAction = isWooShippingConnected
		? 'Manage'
		: primaryPartner.action;

	function ProviderLogo( { partner } ) {
		return (
			<span
				className={ `shipping-provider-logo is-${ partner.id }` }
				aria-hidden="true"
			>
				<img src={ partner.logo } alt={ partner.logoAlt } />
			</span>
		);
	}

	function OfficialBadge() {
		return (
			<span className="shipping-provider-official-badge">
				<span
					className="shipping-provider-official-mark"
					aria-hidden="true"
				>
					w
				</span>
				Official
			</span>
		);
	}

	const renderProviderRow = ( {
		partner,
		isFeatured = false,
		isConnected = false,
		action,
		onAction,
	} ) => (
		<section
			key={ partner.id }
			className={ `shipping-provider-row${
				isFeatured ? ' is-featured' : ''
			}` }
			aria-label={ partner.name }
		>
			<div className="shipping-provider-row-main">
				<ProviderLogo partner={ partner } />
				<div className="shipping-provider-row-text">
					<span className="shipping-provider-title">
						{ partner.name }
						{ partner.isPrimary && <OfficialBadge /> }
					</span>
					<span className="shipping-provider-description">
						{ partner.description }
					</span>
					{ partner.services && (
						<span className="shipping-provider-service-pills">
							{ partner.services.map( ( service ) => (
								<span key={ service }>{ service }</span>
							) ) }
						</span>
					) }
				</div>
			</div>
			<div className="shipping-provider-row-actions">
				<Button
					variant={
						isFeatured && ! isConnected
							? 'primary'
							: isFeatured && isConnected
							? 'tertiary'
							: 'secondary'
					}
					__next40pxDefaultSize
					onClick={ onAction }
				>
					{ action }
				</Button>
				<Button
					variant="tertiary"
					icon={ moreVertical }
					label={ `More actions for ${ partner.name }` }
					__next40pxDefaultSize
				/>
			</div>
		</section>
	);

	return (
		<div
			className={ `shipping-provider-choice${
				isSettingsSurface ? ' is-settings-surface' : ''
			}` }
		>
			{ ! isSettingsSurface && (
				<div className="shipping-provider-toolbar">
					{ isWooShippingConnected ? (
						<span aria-hidden="true" />
					) : (
						<Button
							variant="tertiary"
							__next40pxDefaultSize
							onClick={ onBack }
						>
							Back to setup tasks
						</Button>
					) }
					<button
						type="button"
						aria-label="More options"
						className="shipping-provider-more"
					>
						⋮
					</button>
				</div>
			) }

			<div className="shipping-provider-list-toolbar">
				<h2>Shipping providers</h2>
				<div className="shipping-provider-list-actions">
					<button
						type="button"
						className="shipping-provider-location-button"
					>
						<span>Business location:</span>
						<strong>United States</strong>
						<Icon icon={ chevronDownSmall } size={ 20 } />
					</button>
					<Button
						className="shipping-provider-more-button"
						variant="tertiary"
						icon={ moreVertical }
						label="More shipping provider actions"
						__next40pxDefaultSize
					/>
				</div>
			</div>

			<div
				className="shipping-provider-list"
				aria-label="Shipping provider options"
			>
				{ renderProviderRow( {
					partner: primaryPartner,
					isFeatured: true,
					isConnected: isWooShippingConnected,
					action: primaryAction,
					onAction: isWooShippingConnected
						? onManageWooShipping
						: onStartSetup,
				} ) }

				{ otherPartners.map( ( partner ) =>
					renderProviderRow( {
						partner,
						action: partner.action,
						onAction: () => setSelectedPartner( partner ),
					} )
				) }
			</div>
			{ selectedPartner && (
				<span className="screen-reader-text" role="status">
					{ selectedPartner.name } installation would continue in the
					provider setup flow.
				</span>
			) }
		</div>
	);
}
