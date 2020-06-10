/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { Button } from '@wordpress/components';
import { useState } from 'react';
import { withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { H, Plugins } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/wc-admin-settings';
import { PLUGINS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import withSelect from 'wc-api/with-select';
import Connect from 'dashboard/components/connect';
import { recordEvent } from 'lib/tracks';

function InstallJetpackCta( props ) {
	const {
		isJetpackInstalled,
		isJetpackActivated,
		isJetpackConnected,
		getCurrentUserData,
		updateCurrentUserData,
	} = props;
	const [ isInstalling, setIsInstalling ] = useState( false );
	const [ isConnecting, setIsConnecting ] = useState( false );
	const [ isDismissed, setIsDismissed ] = useState(
		( getCurrentUserData().homepage_stats || {} ).installJetpackDismissed
	);

	function install() {
		setIsInstalling( true );
		recordEvent( 'statsoverview_install_jetpack' );
	}

	function dismiss() {
		if ( isInstalling || isConnecting ) {
			return;
		}

		const homepageStats = getCurrentUserData().homepage_stats || {};

		homepageStats.installJetpackDismissed = true;

		updateCurrentUserData( { homepage_stats: homepageStats } );

		setIsDismissed( true );
		recordEvent( 'statsoverview_dismiss_install_jetpack' );
	}

	function getPluginInstaller() {
		return (
			<Plugins
				autoInstall
				onComplete={ () => {
					setIsInstalling( false );
					setIsConnecting( ! isJetpackConnected );
				} }
				onError={ () => {
					setIsInstalling( false );
				} }
				pluginSlugs={ [ 'jetpack' ] }
			/>
		);
	}

	function getConnector() {
		return (
			<Connect
				autoConnect
				onError={ () => setIsConnecting( false ) }
				redirectUrl={ getAdminLink( 'admin.php?page=wc-admin' ) }
			/>
		);
	}

	const doNotShow =
		isDismissed ||
		( isJetpackInstalled && isJetpackActivated && isJetpackConnected );
	if ( doNotShow ) {
		return null;
	}

	function getInstallJetpackText() {
		if ( ! isJetpackInstalled ) {
			return __( 'Get Jetpack', 'woocommerce-admin' );
		}

		if ( ! isJetpackActivated ) {
			return __( 'Activate Jetpack', 'woocommerce-admin' );
		}

		if ( ! isJetpackConnected ) {
			return __( 'Connect Jetpack', 'woocommerce-admin' );
		}

		return '';
	}

	return (
		<article className="woocommerce-stats-overview__install-jetpack-promo">
			<H>
				{ __( 'Get traffic stats with Jetpack', 'woocommerce-admin' ) }
			</H>
			<p>
				{ __(
					'Keep an eye on your views and visitors metrics with ' +
						'Jetpack. Requires Jetpack plugin and a WordPress.com ' +
						'account.',
					'woocommerce-admin'
				) }
			</p>
			<footer>
				<Button isPrimary onClick={ install } isBusy={ isInstalling }>
					{ getInstallJetpackText() }
				</Button>
				<Button onClick={ dismiss } isBusy={ isInstalling }>
					{ __( 'No thanks', 'woocommerce-admin' ) }
				</Button>
			</footer>

			{ isInstalling && getPluginInstaller() }
			{ isConnecting && getConnector() }
		</article>
	);
}

InstallJetpackCta.propTypes = {
	/**
	 * Is the Jetpack plugin connected.
	 */
	isJetpackConnected: PropTypes.bool.isRequired,
	/**
	 * A method to get user meta.
	 */
	getCurrentUserData: PropTypes.func.isRequired,
	/**
	 * A method to update user meta.
	 */
	updateCurrentUserData: PropTypes.func.isRequired,
};

export default compose(
	withSelect( ( select ) => {
		const {
			isJetpackConnected,
			getInstalledPlugins,
			getActivePlugins,
		} = select( PLUGINS_STORE_NAME );
		const { getCurrentUserData } = select( 'wc-api' );

		return {
			isJetpackInstalled: getInstalledPlugins().includes( 'jetpack' ),
			isJetpackActivated: getActivePlugins().includes( 'jetpack' ),
			isJetpackConnected: isJetpackConnected(),
			getCurrentUserData,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( InstallJetpackCta );
