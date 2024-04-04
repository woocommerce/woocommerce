/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import { getWcsAssets, acceptWcsTos } from '../wcs-api';

const wcAssetUrl = getSetting( 'wcAssetUrl', '' );
const wcsPluginSlug = 'woocommerce-services';

export class ShippingBanner extends Component {
	constructor( props ) {
		super( props );

		const orderId = new URL( window.location.href ).searchParams.get(
			'post'
		);

		this.state = {
			showShippingBanner: true,
			isDismissModalOpen: false,
			setupErrorReason: setupErrorTypes.SETUP,
			orderId: parseInt( orderId, 10 ),
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

	acceptTosAndGetWCSAssets() {
		return acceptWcsTos()
			.then( () => getWcsAssets() )
			.then( ( wcsAssets ) => this.loadWcsAssets( wcsAssets ) )
			.catch( () => this.setState( { wcsSetupError: true } ) );
	}

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
		<div class="wcc-root woocommerce wc-connect-create-shipping-label" data-args="${ argsJsonString }">
		</div>
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

		const jsPath = assets.wc_connect_admin_script;
		const stylePath = assets.wc_connect_admin_style;

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
					const head = document.getElementsByTagName( 'head' )[ 0 ];
					const link = document.createElement( 'link' );
					link.rel = 'stylesheet';
					link.type = 'text/css';
					link.href = stylePath;
					link.media = 'all';
					link.onload = resolve;
					link.onerror = reject;
					head.appendChild( link );
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
			this.openWcsModal();
		} );
	}

	getInstallText = () => {
		const { activePlugins } = this.props;
		if ( activePlugins.includes( wcsPluginSlug ) ) {
			// If WCS is active, then the only remaining step is to agree to the ToS.
			return __(
				'You\'ve already installed WooCommerce Shipping. By clicking "Create shipping label", you agree to its {{tosLink}}Terms of Service{{/tosLink}}.',
				'woocommerce'
			);
		}
		return __(
			'By clicking "Create shipping label", {{wcsLink}}WooCommerce Shipping{{/wcsLink}} will be installed and you agree to its {{tosLink}}Terms of Service{{/tosLink}}.',
			'woocommerce'
		);
	};

	openWcsModal() {
		if ( window.wcsGetAppStoreAsync ) {
			window
				.wcsGetAppStoreAsync( 'wc-connect-create-shipping-label' )
				.then( ( wcsStore ) => {
					const state = wcsStore.getState();
					const { orderId } = this.state;
					const siteId = state.ui.selectedSiteId;

					const wcsStoreUnsubscribe = wcsStore.subscribe( () => {
						const latestState = wcsStore.getState();

						const shippingLabelState = get(
							latestState,
							[
								'extensions',
								'woocommerce',
								'woocommerceServices',
								siteId,
								'shippingLabel',
								orderId,
							],
							null
						);

						const labelSettingsState = get(
							latestState,
							[
								'extensions',
								'woocommerce',
								'woocommerceServices',
								siteId,
								'labelSettings',
							],
							null
						);

						const packageState = get(
							latestState,
							[
								'extensions',
								'woocommerce',
								'woocommerceServices',
								siteId,
								'packages',
							],
							null
						);

						const locationsState = get( latestState, [
							'extensions',
							'woocommerce',
							'sites',
							siteId,
							'data',
							'locations',
						] );

						if (
							shippingLabelState &&
							labelSettingsState &&
							labelSettingsState.meta &&
							packageState &&
							locationsState
						) {
							if (
								shippingLabelState.loaded &&
								labelSettingsState.meta.isLoaded &&
								packageState.isLoaded &&
								isArray( locationsState ) &&
								! this.state.isWcsModalOpen
							) {
								if ( window.jQuery ) {
									this.setState( { isWcsModalOpen: true } );
									window
										.jQuery(
											'.shipping-label__new-label-button'
										)
										.click();
								}
								wcsStore.dispatch( {
									type: 'NOTICE_CREATE',
									notice: {
										duration: 10000,
										status: 'is-success',
										text: __(
											'Plugin installed and activated',
											'woocommerce'
										),
									},
								} );
							} else if (
								shippingLabelState.showPurchaseDialog
							) {
								wcsStoreUnsubscribe();
								if ( window.jQuery ) {
									window
										.jQuery( '#woocommerce-order-label' )
										.show();
								}
							}
						}
					} );

					document.getElementById(
						'woocommerce-admin-print-label'
					).style.display = 'none';
				} );
		}
	}

	render() {
		const {
			isDismissModalOpen,
			showShippingBanner,
			isShippingLabelButtonBusy,
		} = this.state;
		if ( ! showShippingBanner ) {
			return null;
		}

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
											href="woocommerce.com/products/shipping/?utm_medium=product"
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
						{ __( 'Create shipping label', 'woocommerce' ) }
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

		return {
			isRequesting,
			isJetpackConnected: isJetpackConnected(),
			activePlugins: getActivePlugins(),
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
