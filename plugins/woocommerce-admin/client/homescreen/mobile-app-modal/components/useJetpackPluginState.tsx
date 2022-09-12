/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { PLUGINS_STORE_NAME, useUser } from '@woocommerce/data';
import { createErrorNotice } from '@woocommerce/data/src/plugins/actions';

export const JetpackPluginStates = {
	/** Jetpack plugin is not installed, can use installHandler() to install */
	NOT_INSTALLED: 'not-installed',
	/** Installing Jetpack plugin on the WordPress installation */
	INSTALLING: 'installing',
	/** User doesn't have permissions to install plugins on this site */
	USER_CANNOT_INSTALL: 'user-cannot-install',
	/** Weird edge case where the plugin is installed but not activated */
	NOT_ACTIVATED: 'not-activated',
	/** Jetpack plugin is installed but not connected to a WordPress.com user */
	USERLESS_CONNECTION: 'userless-connection',
	/** Jetpack Plugin installed and WordPress.com user connected */
	FULL_CONNECTION: 'full-connection',
	/** Still retrieving Jetpack state from Wordpress Installation */
	INITIALIZING: 'initializing',
} as const;

export type JetpackPluginStates =
	typeof JetpackPluginStates[ keyof typeof JetpackPluginStates ];

/**
 * Utility hook to determine and manipulate the state of the Jetpack plugin on the WordPress installation
 */
export const useJetpackPluginState = () => {
	const { currentUserCan } = useUser();
	const {
		canUserInstallPlugins,
		jetpackInstallState,
		jetpackConnectionData,
	} = useSelect( ( select ) => {
		const { getPluginInstallState, getJetpackConnectionData } =
			select( PLUGINS_STORE_NAME );
		const installState = getPluginInstallState( 'jetpack' );

		return {
			jetpackConnectionData: getJetpackConnectionData(),
			jetpackInstallState: installState,
			canUserInstallPlugins: currentUserCan( 'install_plugins' ),
		};
	} );

	const { installJetpackAndConnect } = useDispatch( PLUGINS_STORE_NAME );

	const [ pluginState, setPluginState ] = useState< JetpackPluginStates >(
		JetpackPluginStates.INITIALIZING
	);

	/**
	 * Installs, Activates, and Connects Jetpack - starting wherever hasn't been completed
	 */
	const onClickInstall = useCallback( () => {
		const thisUrl = window.location.href;
		installJetpackAndConnect(
			createErrorNotice,
			() => thisUrl + '&jetpackState=returning'
		);
		setPluginState( JetpackPluginStates.INSTALLING );
	}, [ installJetpackAndConnect ] );

	useEffect( () => {
		if ( ! canUserInstallPlugins ) {
			setPluginState( JetpackPluginStates.USER_CANNOT_INSTALL );
		}

		if ( jetpackInstallState === 'unavailable' ) {
			setPluginState( JetpackPluginStates.NOT_INSTALLED );
		}

		if ( jetpackInstallState === 'installed' ) {
			setPluginState( JetpackPluginStates.NOT_ACTIVATED );
		}

		if ( jetpackInstallState === 'activated' ) {
			if (
				// Jetpack can be installed and activated but not connected to a WordPress.com user account, this handles that
				jetpackConnectionData &&
				! jetpackConnectionData.currentUser.isConnected
			) {
				setPluginState( JetpackPluginStates.USERLESS_CONNECTION );
			} else if (
				jetpackConnectionData &&
				jetpackConnectionData.currentUser.isConnected
			) {
				setPluginState( JetpackPluginStates.FULL_CONNECTION );
			}
		}
	}, [ canUserInstallPlugins, jetpackInstallState, jetpackConnectionData ] );

	return {
		state: pluginState,
		installHandler: onClickInstall,
		jetpackConnectionData,
	};
};
