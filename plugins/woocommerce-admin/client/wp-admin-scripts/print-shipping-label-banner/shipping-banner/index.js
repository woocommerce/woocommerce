/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, ExternalLink } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import interpolateComponents from '@automattic/interpolate-components';
import PropTypes from 'prop-types';
import { get, isArray } from 'lodash';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { withDispatch, withSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import '../style.scss';
import DismissModal from '../dismiss-modal';
import SetupNotice, { setupErrorTypes } from '../setup-notice';
import {
	getWcsAssets,
	acceptWcsTos,
	getWcsLabelPurchaseConfigs,
} from '../wcs-api';

const wcAssetUrl = getSetting( 'wcAssetUrl', '' );
const wcsPluginSlug = 'woocommerce-shipping';
const wcstPluginSlug = 'woocommerce-services';

export class ShippingBanner extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			showShippingBanner: true,
			isDismissModalOpen: false,
			setupErrorReason: setupErrorTypes.SETUP,
			orderId: parseInt( wcShippingCoreData.order_id, 10 ),
			wcsAssetsLoaded: false,
			wcsAssetsLoading: false,
			wcsSetupError: false,
			isShippingLabelButtonBusy: false,
			installText: this.getInstallText(),
			isWcsModalOpen: false,
		};
	}

	componentDidMount() {
		const { showShippingBanner } = this.state;

		if ( showShippingBanner ) {
			this.trackImpression();
		}
	}

	isSetupError = () => this.state.wcsSetupError;

	closeDismissModal = () => {
		this.setState( { isDismissModalOpen: false } );
		this.trackElementClicked(
			'shipping_banner_dismiss_modal_close_button'
		);
	};

	openDismissModal = () => {
		this.setState( { isDismissModalOpen: true } );
		this.trackElementClicked( 'shipping_banner_dimiss' );
	};

	hideBanner = () => {
		this.setState( { showShippingBanner: false } );
	};

	createShippingLabelClicked = () => {
		const { activePlugins } = this.props;
		this.setState( { isShippingLabelButtonBusy: true } );
		this.trackElementClicked( 'shipping_banner_create_label' );
		if ( ! activePlugins.includes( wcsPluginSlug ) ) {
			this.installAndActivatePlugins( wcsPluginSlug );
		} else {
			this.acceptTosAndGetWCSAssets();
		}
	};

	async installAndActivatePlugins( pluginSlug ) {
		// Avoid double activating.
		const { installPlugins, activatePlugins, isRequesting } = this.props;
		if ( isRequesting ) {
			return false;
		}
		const install = await installPlugins( [ pluginSlug ] );
		if ( install.success !== true ) {
			this.setState( {
				setupErrorReason: setupErrorTypes.INSTALL,
				wcsSetupError: true,
			} );
			return;
		}

		const activation = await activatePlugins( [ pluginSlug ] );
		if ( activation.success !== true ) {
			this.setState( {
				setupErrorReason: setupErrorTypes.ACTIVATE,
				wcsSetupError: true,
			} );
			return;
		}

		this.acceptTosAndGetWCSAssets();
	}

	woocommerceServiceLinkClicked = () => {
		this.trackElementClicked( 'shipping_banner_woocommerce_service_link' );
	};

	trackBannerEvent = ( eventName, customProps = {} ) => {
		const { activePlugins, isJetpackConnected } = this.props;
		recordEvent( eventName, {
			banner_name: 'wcadmin_install_wcs_prompt',
			jetpack_installed: activePlugins.includes( 'jetpack' ),
			jetpack_connected: isJetpackConnected,
			wcs_installed: activePlugins.includes( wcsPluginSlug ),
			...customProps,
		} );
	};

	trackImpression = () => {
		this.trackBannerEvent( 'banner_impression' );
	};

	trackElementClicked = ( element ) => {
		this.trackBannerEvent( 'banner_element_clicked', {
			element,
		} );
	};

	acceptTosAndGetWCSAssets = () => {
		return acceptWcsTos()
			.then( () => getWcsLabelPurchaseConfigs( this.state.orderId ) )
			.then( ( configs ) => {
				window.WCShipping_Config = configs.config;
				return configs;
			} )
			.then( () => getWcsAssets() )
			.then( ( wcsAssets ) => this.loadWcsAssets( wcsAssets ) )
			.catch( ( err ) => {
				this.setState( { wcsSetupError: true } );
			} );
	};

	generateMetaBoxHtml( nodeId, title, args ) {
		const argsJsonString = JSON.stringify( args ).replace( /"/g, '&quot;' ); // JS has no native html_entities so we just replace.

		const togglePanelText = __( 'Toggle panel:', 'woocommerce' );

		return `
<div id="${ nodeId }" class="postbox">
	<div class="postbox-header">
		<h2 class="hndle"><span>${ title }</span></h2>
		<div class="handle-actions">
			<button type="button" class="handlediv" aria-expanded="true">
				<span class="screen-reader-text">${ togglePanelText } ${ title }</span>
				<span class="toggle-indicator" aria-hidden="true"></span>
			</button>
		</div>
	</div>
	<div class="inside">
		<div class="wcc-root woocommerce woocommerce-shipping-shipping-label" id="woocommerce-shipping-shipping-label-${ args.context }"></div>
	</div>
</div>
`;
	}

	loadWcsAssets( { assets } ) {
		if ( this.state.wcsAssetsLoaded || this.state.wcsAssetsLoading ) {
			this.openWcsModal();
			return;
		}

		this.setState( { wcsAssetsLoading: true } );

		const jsPath = assets.wcshipping_create_label_script;
		const stylePath = assets.wcshipping_create_label_style;

		if ( undefined === window.wcsPluginData ) {
			const assetPath = jsPath.substring(
				0,
				jsPath.lastIndexOf( '/' ) + 1
			);
			window.wcsPluginData = { assetPath };
		}

		const { orderId } = this.state;
		const { itemsCount } = this.props;

		const shippingLabelContainerHtml = this.generateMetaBoxHtml(
			'woocommerce-order-label',
			__( 'Shipping Label', 'woocommerce' ),
			{
				order: { id: orderId },
				context: 'shipping_label',
				items: itemsCount,
			}
		);
		// Insert shipping label metabox just above main order details box.
		document
			.getElementById( 'woocommerce-order-data' )
			.insertAdjacentHTML( 'beforebegin', shippingLabelContainerHtml );

		const shipmentTrackingHtml = this.generateMetaBoxHtml(
			'woocommerce-order-shipment-tracking',
			__( 'Shipment Tracking', 'woocommerce' ),
			{
				order: { id: orderId },
				context: 'shipment_tracking',
				items: itemsCount,
			}
		);
		// Insert tracking metabox in the side after the order actions.
		document
			.getElementById( 'woocommerce-order-actions' )
			.insertAdjacentHTML( 'afterend', shipmentTrackingHtml );

		if ( window.jQuery ) {
			// Need to refresh so the new metaboxes are sortable.
			window.jQuery( '#normal-sortables' ).sortable( 'refresh' );
			window.jQuery( '#side-sortables' ).sortable( 'refresh' );

			window.jQuery( '#woocommerce-order-label' ).hide();
		}

		Promise.all( [
			new Promise( ( resolve, reject ) => {
				const script = document.createElement( 'script' );
				script.src = jsPath;
				script.async = true;
				script.onload = resolve;
				script.onerror = reject;
				document.body.appendChild( script );
			} ),
			new Promise( ( resolve, reject ) => {
				if ( stylePath !== '' ) {
					const link = document.createElement( 'link' );
					link.rel = 'stylesheet';
					link.type = 'text/css';
					link.href = stylePath;
					link.media = 'all';
					link.onload = resolve;
					link.onerror = reject;
					link.id = 'wcshipping-injected-styles';
					document.head.appendChild( link );
				} else {
					resolve();
				}
			} ),
		] ).then( () => {
			this.setState( {
				wcsAssetsLoaded: true,
				wcsAssetsLoading: false,
				isShippingLabelButtonBusy: false,
			} );

			// Reshow the shipping label metabox.
			if ( window.jQuery ) {
				window.jQuery( '#woocommerce-order-label' ).show();
			}

			document.getElementById(
				'woocommerce-admin-print-label'
			).style.display = 'none';

			this.openWcsModal();
		} );
	}

	getInstallText = () => {
		const { activePlugins, actionButtonLabel } = this.props;
		if ( activePlugins.includes( wcsPluginSlug ) ) {
			// If WCS is active, then the only remaining step is to agree to the ToS.
			return __(
				'You\'ve already installed WooCommerce Shipping. By clicking "Create shipping label", you agree to its {{tosLink}}Terms of Service{{/tosLink}}.',
				'woocommerce'
			);
		}
		return sprintf(
			__(
				'By clicking "%s", {{wcsLink}}WooCommerce Shipping{{/wcsLink}} will be installed and you agree to its {{tosLink}}Terms of Service{{/tosLink}}.',
				'woocommerce'
			),
			actionButtonLabel
		);
	};

	openWcsModal = () => {
		// Since the button is dynamically added, we need to wait for it to become selectable and then click it.

		const buttonSelector =
			'#woocommerce-shipping-shipping-label-shipping_label button';
		if ( MutationObserver ) {
			const observer = new MutationObserver(
				( mutationsList, observer ) => {
					const button = document.querySelector( buttonSelector );
					if ( button ) {
						button.click();
						observer.disconnect();
					}
				}
			);

			observer.observe(
				document.getElementById(
					'woocommerce-shipping-shipping-label-shipping_label'
				),
				{
					childList: true,
					subtree: true,
				}
			);
		} else {
			const interval = setInterval( () => {
				const targetElement = document.querySelector( buttonSelector );
				if ( targetElement ) {
					targetElement.click();
					clearInterval( interval );
				}
			}, 300 );
		}
	};

	render() {
		const {
			isDismissModalOpen,
			showShippingBanner,
			isShippingLabelButtonBusy,
		} = this.state;
		if ( ! showShippingBanner ) {
			return null;
		}

		const { actionButtonLabel } = this.props;
		return (
			<div>
				<div className="wc-admin-shipping-banner-container">
					<img
						className="wc-admin-shipping-banner-illustration"
						src={ wcAssetUrl + 'images/shippingillustration.svg' }
						alt={ __( 'Shipping ', 'woocommerce' ) }
					/>
					<div className="wc-admin-shipping-banner-blob">
						<h3>
							{ __(
								'Print discounted shipping labels with a click.',
								'woocommerce'
							) }
						</h3>
						<p>
							{ interpolateComponents( {
								mixedString: this.state.installText,
								components: {
									tosLink: (
										<ExternalLink
											href="https://wordpress.com/tos"
											target="_blank"
											type="external"
										/>
									),
									wcsLink: (
										<ExternalLink
											href="https://woocommerce.com/products/shipping/?utm_medium=product"
											target="_blank"
											type="external"
											onClick={
												this
													.woocommerceServiceLinkClicked
											}
										/>
									),
								},
							} ) }
						</p>
						<SetupNotice
							isSetupError={ this.isSetupError() }
							errorReason={ this.state.setupErrorReason }
						/>
					</div>
					<Button
						disabled={ isShippingLabelButtonBusy }
						isPrimary
						isBusy={ isShippingLabelButtonBusy }
						onClick={ this.createShippingLabelClicked }
					>
						{ actionButtonLabel }
					</Button>

					<button
						onClick={ this.openDismissModal }
						type="button"
						className="notice-dismiss"
						disabled={ this.state.isShippingLabelButtonBusy }
					>
						<span className="screen-reader-text">
							{ __( 'Close Print Label Banner.', 'woocommerce' ) }
						</span>
					</button>
				</div>
				<DismissModal
					visible={ isDismissModalOpen }
					onClose={ this.closeDismissModal }
					onCloseAll={ this.hideBanner }
					trackElementClicked={ this.trackElementClicked }
				/>
			</div>
		);
	}
}

ShippingBanner.propTypes = {
	itemsCount: PropTypes.number.isRequired,
	isJetpackConnected: PropTypes.bool.isRequired,
	activePlugins: PropTypes.array.isRequired,
	activatePlugins: PropTypes.func.isRequired,
	installPlugins: PropTypes.func.isRequired,
	isRequesting: PropTypes.bool.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const { isPluginsRequesting, isJetpackConnected, getActivePlugins } =
			select( PLUGINS_STORE_NAME );

		const isRequesting =
			isPluginsRequesting( 'activatePlugins' ) ||
			isPluginsRequesting( 'installPlugins' );
		const activePlugins = getActivePlugins();
		const actionButtonLabel = activePlugins.includes( wcstPluginSlug )
			? __( 'Install WooCommerce Shipping', 'woocommerce' )
			: __( 'Create shipping label', 'woocommerce' );

		return {
			isRequesting,
			isJetpackConnected: isJetpackConnected(),
			activePlugins,
			actionButtonLabel,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { activatePlugins, installPlugins } =
			dispatch( PLUGINS_STORE_NAME );

		return {
			activatePlugins,
			installPlugins,
		};
	} )
)( ShippingBanner );
