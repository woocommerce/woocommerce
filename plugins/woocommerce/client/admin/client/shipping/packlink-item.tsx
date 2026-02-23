/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { Button, ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './woocommerce-shipping-item.scss';

const PACKLINK_PLUGIN_SLUG = 'packlink-pro-shipping';

const PacklinkItem = ( {
	isPluginInstalled,
	onSetupClick,
	pluginsBeingSetup,
}: {
	isPluginInstalled: boolean;
	pluginsBeingSetup: Array< string >;
	onSetupClick: ( slugs: string[] ) => PromiseLike< void >;
} ) => {
	const { createSuccessNotice } = useDispatch( 'core/notices' );

	const handleSetupClick = () => {
		onSetupClick( [ PACKLINK_PLUGIN_SLUG ] ).then(
			() => {
				createSuccessNotice(
					__( 'Packlink PRO is installed!', 'woocommerce' ),
					{}
				);
			},
			() => {}
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
					isSecondary
					onClick={ handleSetupClick }
					isBusy={ pluginsBeingSetup.includes(
						PACKLINK_PLUGIN_SLUG
					) }
					disabled={ pluginsBeingSetup.length > 0 }
				>
					{ isPluginInstalled
						? __( 'Activate', 'woocommerce' )
						: __( 'Get started', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};

export default PacklinkItem;
