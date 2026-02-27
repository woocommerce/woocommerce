/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';
import { Pill } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';
import WooIcon from './woo-icon.svg';

const WOOCOMMERCE_SHIPPING_PLUGIN_SLUG = 'woocommerce-shipping';

export type ShippingPartnerTrackingProps = {
	context: 'settings';
	country: string;
	plugins: string;
};

const WooCommerceShippingItem = ( {
	isPluginInstalled,
	onInstallClick,
	onActivateClick,
	pluginsBeingSetup,
	tracking,
}: {
	isPluginInstalled: boolean;
	pluginsBeingSetup: Array< string >;
	onInstallClick: ( slugs: string[] ) => PromiseLike< void >;
	onActivateClick: ( slugs: string[] ) => PromiseLike< void >;
	tracking?: ShippingPartnerTrackingProps;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleClick = () => {
		const trackingBase = {
			...( tracking ?? {} ),
			selected_plugin: WOOCOMMERCE_SHIPPING_PLUGIN_SLUG,
		};

		recordEvent( 'shipping_partner_click', trackingBase );

		const action = isPluginInstalled ? onActivateClick : onInstallClick;
		const eventName = isPluginInstalled
			? 'shipping_partner_activate'
			: 'shipping_partner_install';

		action( [ WOOCOMMERCE_SHIPPING_PLUGIN_SLUG ] ).then(
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: true,
				} );
				createSuccessNotice(
					isPluginInstalled
						? __( 'WooCommerce Shipping activated!', 'woocommerce' )
						: __(
								'WooCommerce Shipping is installed!',
								'woocommerce'
						  ),
					{}
				);
			},
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: false,
				} );
			}
		);
	};

	return (
		<div className="woocommerce-list__item-inner woocommerce-shipping-plugin-item">
			<div className="woocommerce-list__item-before">
				<img
					className="woocommerce-shipping-plugin-item__logo"
					src={ WooIcon }
					alt="WooCommerce Shipping Logo"
				/>
			</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'WooCommerce Shipping', 'woocommerce' ) }
					<Pill>{ __( 'Recommended', 'woocommerce' ) }</Pill>
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Print USPS, UPS, and DHL Express labels straight from your WooCommerce dashboard and save on shipping.',
						'woocommerce'
					) }
					<br />
					<ExternalLink href="https://woocommerce.com/woocommerce-shipping/">
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					variant={ isPluginInstalled ? 'primary' : 'secondary' }
					onClick={ handleClick }
					isBusy={ pluginsBeingSetup.includes(
						WOOCOMMERCE_SHIPPING_PLUGIN_SLUG
					) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ isPluginInstalled
						? __( 'Activate', 'woocommerce' )
						: __( 'Install', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default WooCommerceShippingItem;
