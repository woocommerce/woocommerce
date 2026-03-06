/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';
import type { ShippingPartnerTrackingProps } from './experimental-woocommerce-shipping-item';

const PACKLINK_PLUGIN_SLUG = 'packlink-pro-shipping';

const PacklinkItem = ( {
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
			selected_plugin: PACKLINK_PLUGIN_SLUG,
		};

		recordEvent( 'shipping_partner_click', trackingBase );

		const action = isPluginInstalled ? onActivateClick : onInstallClick;
		const eventName = isPluginInstalled
			? 'shipping_partner_activate'
			: 'shipping_partner_install';

		action( [ PACKLINK_PLUGIN_SLUG ] ).then(
			() => {
				recordEvent( eventName, {
					...trackingBase,
					success: true,
				} );
				createSuccessNotice(
					isPluginInstalled
						? __( 'Packlink PRO activated!', 'woocommerce' )
						: __( 'Packlink PRO is installed!', 'woocommerce' ),
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
					src="https://ps.w.org/packlink-pro-shipping/assets/icon-128x128.png"
					alt=""
				/>
			</div>
			<div className="woocommerce-list__item-text">
				<span className="woocommerce-list__item-title">
					{ __( 'Packlink PRO', 'woocommerce' ) }
				</span>
				<span className="woocommerce-list__item-content">
					{ __(
						'Leverage a multi-carrier shipping platform that automates order shipping and delivery, optimizes logistics, and offers pre-negotiated rates with carriers such as Royal Mail, Evri, UPS, DPD, Yodel and GlobalPost. Manage orders, print shipping labels individually or in bulk, track shipments in real time, and handle returns from a single dashboard.',
						'woocommerce'
					) }
					<br />
					<ExternalLink href="https://woocommerce.com/products/packlink-pro/">
						{ __( 'Learn more', 'woocommerce' ) }
					</ExternalLink>
				</span>
			</div>
			<div className="woocommerce-list__item-after">
				<Button
					variant={ isPluginInstalled ? 'primary' : 'secondary' }
					onClick={ handleClick }
					isBusy={ pluginsBeingSetup.includes(
						PACKLINK_PLUGIN_SLUG
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

export default PacklinkItem;
