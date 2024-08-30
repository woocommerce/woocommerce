/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { Button, ExternalLink } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import interpolateComponents from '@automattic/interpolate-components';
import PropTypes from 'prop-types';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';
import { withDispatch, withSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { getSetting, getAdminLink } from '@woocommerce/settings';
import { Link } from '@woocommerce/components';

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
const wcShippingPluginSlug = 'woocommerce-shipping';
const wcstPluginSlug = 'woocommerce-services';

export class ShippingBanner extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			showShippingBanner: true,
			isDismissModalOpen: false,
			setupErrorReason: setupErrorTypes.SETUP,
			wcsAssetsLoaded: false,
			wcsAssetsLoading: false,
			wcsSetupError: false,
			isShippingLabelButtonBusy: false,
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
		if ( ! activePlugins.includes( wcShippingPluginSlug ) ) {
			this.installAndActivatePlugins( wcShippingPluginSlug );
		} else {
			this.acceptTosAndGetWCSAssets();
		}
	};

	async installAndActivatePlugins( pluginSlug ) {
		// Avoid double activating.
		const {
			installPlugins,
			activatePlugins,
			isRequesting,
			activePlugins,
			isWcstCompatible,
			isIncompatibleWCShippingInstalled,
		} = this.props;
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

		/**
		 * If a incompatible version of the WooCommerce Shipping plugin is installed, the necessary endpoints
		 * are not available, so we need to reload the page to ensure to make the plugin usable.
		 */
		if ( isIncompatibleWCShippingInstalled ) {
			window.location.reload( true );
			return;
		}

		if (
			! activePlugins.includes( wcShippingPluginSlug ) &&
			isWcstCompatible
		) {
			this.acceptTosAndGetWCSAssets();
		} else {
			this.setState( {
				showShippingBanner: false,
			} );
		}
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
			wcs_installed: activePlugins.includes( wcShippingPluginSlug ),
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
			.then( () => getWcsLabelPurchaseConfigs( this.props.orderId ) )
			.then( ( configs ) => {
				window.WCShipping_Config = configs.config;
				return configs;
			} )
			.then( () => getWcsAssets() )
			.then( ( wcsAssets ) => this.loadWcsAssets( wcsAssets ) )
			.catch( () => {
				this.setState( { wcsSetupError: true } );
			} );
	};

	generateMetaBoxHtml( nodeId, title, args ) {
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

		const labelPurchaseMetaboxId = 'woocommerce-order-label';
		const shipmentTrackingMetaboxId = 'woocommerce-order-shipment-tracking';
		const jsPath = assets.wcshipping_create_label_script;
		const stylePath = assets.wcshipping_create_label_style;

		const shipmentTrackingJsPath =
			assets.wcshipping_shipment_tracking_script;
		const shipmentTrackingStylePath =
			assets.wcshipping_shipment_tracking_style;

		const { activePlugins } = this.props;

		document.getElementById( labelPurchaseMetaboxId )?.remove();
		const shippingLabelContainerHtml = this.generateMetaBoxHtml(
			labelPurchaseMetaboxId,
			__( 'Shipping Label', 'woocommerce' ),
			{
				context: 'shipping_label',
			}
		);
		// Insert shipping label metabox just above main order details box.
		document
			.getElementById( 'woocommerce-order-data' )
			.insertAdjacentHTML( 'beforebegin', shippingLabelContainerHtml );

		document.getElementById( shipmentTrackingMetaboxId )?.remove();
		const shipmentTrackingHtml = this.generateMetaBoxHtml(
			shipmentTrackingMetaboxId,
			__( 'Shipment Tracking', 'woocommerce' ),
			{
				context: 'shipment_tracking',
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

		document
			.querySelectorAll( 'script[src*="/woocommerce-services/"]' )
			.forEach( ( node ) => node.remove?.() );
		document
			.querySelectorAll( 'link[href*="/woocommerce-services/"]' )
			.forEach( ( node ) => node.remove?.() );

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
				const script = document.createElement( 'script' );
				script.src = shipmentTrackingJsPath;
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
			new Promise( ( resolve, reject ) => {
				if ( shipmentTrackingStylePath !== '' ) {
					const link = document.createElement( 'link' );
					link.rel = 'stylesheet';
					link.type = 'text/css';
					link.href = shipmentTrackingStylePath;
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

			/**
			 * We'll only get to this point if either WCS&T is not active or is active but compatible with WooCommerce Shipping
			 * so once we check if the WCS&T is not active, we can open the label purchase modal immediately.
			 */
			if ( ! activePlugins.includes( wcstPluginSlug ) ) {
				this.openWcsModal();
			}
		} );
	}

	openWcsModal() {
		// Since the button is dynamically added, we need to wait for it to become selectable and then click it.

		const buttonSelector =
			'#woocommerce-shipping-shipping-label-shipping_label button';
		if ( window.MutationObserver ) {
			const observer = new window.MutationObserver(
				( mutationsList, observing ) => {
					const button = document.querySelector( buttonSelector );
					if ( button ) {
						button.click();
						observing.disconnect();
					}
				}
			);

			observer.observe(
				document.getElementById(
					'woocommerce-shipping-shipping-label-shipping_label'
				) ??
					document.getElementById( 'wpbody-content' ) ??
					document.body,
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
	}

	render() {
		const {
			isDismissModalOpen,
			showShippingBanner,
			isShippingLabelButtonBusy,
		} = this.state;
		const { isWcstCompatible } = this.props;
		if ( ! showShippingBanner && ! isWcstCompatible ) {
			document
				.getElementById( 'woocommerce-admin-print-label' )
				.classList.add( 'error' );

			return (
				<p>
					<strong>
						{ interpolateComponents( {
							mixedString: __(
								'Please {{pluginPageLink}}update{{/pluginPageLink}} the WooCommerce Shipping & Tax plugin to the latest version to ensure compatibility with WooCommerce Shipping.',
								'woocommerce'
							),
							components: {
								pluginPageLink: (
									<Link
										href={ getAdminLink( 'plugins.php' ) }
										target="_blank"
										type="wp-admin"
									/>
								),
							},
						} ) }
					</strong>
				</p>
			);
		}

		if ( ! showShippingBanner ) {
			return null;
		}

		const { actionButtonLabel, headline } = this.props;
		return (
			<div>
				<div className="wc-admin-shipping-banner-container">
					<img
						className="wc-admin-shipping-banner-illustration"
						src={ wcAssetUrl + 'images/shippingillustration.svg' }
						alt={ __( 'Shipping ', 'woocommerce' ) }
					/>
					<div className="wc-admin-shipping-banner-blob">
						<h3>{ headline }</h3>
						<p>
							{ interpolateComponents( {
								mixedString: sprintf(
									// translators: %s is the action button label.
									__(
										'By clicking "%s", {{wcsLink}}WooCommerce Shipping{{/wcsLink}} will be installed and you agree to its {{tosLink}}Terms of Service{{/tosLink}}.',
										'woocommerce'
									),
									actionButtonLabel
								),
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
	isJetpackConnected: PropTypes.bool.isRequired,
	activePlugins: PropTypes.array.isRequired,
	activatePlugins: PropTypes.func.isRequired,
	installPlugins: PropTypes.func.isRequired,
	isRequesting: PropTypes.bool.isRequired,
	orderId: PropTypes.number.isRequired,
	isWcstCompatible: PropTypes.bool.isRequired,
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
		const headline = activePlugins.includes( wcstPluginSlug )
			? __(
					'Print discounted shipping labels with a click, now with the dedicated plugin!',
					'woocommerce'
			  )
			: __(
					'Print discounted shipping labels with a click.',
					'woocommerce'
			  );
		return {
			isRequesting,
			isJetpackConnected: isJetpackConnected(),
			activePlugins,
			actionButtonLabel,
			headline,
			orderId: parseInt( window.wcShippingCoreData.order_id, 10 ),
			isWcstCompatible: [ 1, '1' ].includes(
				window.wcShippingCoreData.is_wcst_compatible
			),
			isIncompatibleWCShippingInstalled: [ 1, '1' ].includes(
				window.wcShippingCoreData.is_incompatible_wcshipping_installed
			),
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
