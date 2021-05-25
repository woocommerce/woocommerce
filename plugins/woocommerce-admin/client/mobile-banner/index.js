/**
 * External dependencies
 */
import React, { useEffect, useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import GridiconCrossIcon from 'gridicons/dist/cross-small';

/**
 * Internal dependencies
 */
import { platform, ANDROID_PLATFORM } from '../lib/platform';
import { AppIcon } from './app-icon';
import './style.scss';
import { PLAY_STORE_LINK, TRACKING_EVENT_NAME } from './constants';

const SHOW_APP_BANNER_MODIFIER_CLASS = 'woocommerce-layout__show-app-banner';

export const MobileAppBanner = ( { onInstall, onDismiss } ) => {
	useEffect( () => {
		const layout = document.getElementsByClassName(
			'woocommerce-layout'
		)[ 0 ];

		if ( platform() === ANDROID_PLATFORM ) {
			if ( layout ) {
				// This is a hack to allow the mobile banner to work in the context of the header which is
				// position fixed. This can be refactored away when we move away from the activity panel
				// in future.
				layout.classList.add( SHOW_APP_BANNER_MODIFIER_CLASS );
			}
		}

		return () => {
			if ( layout ) {
				layout.classList.remove( SHOW_APP_BANNER_MODIFIER_CLASS );
			}
		};
	}, [] );

	const [ isDismissed, setDismissed ] = useState( false );

	// On iOS the "Smart App Banner" meta tag is used so only display this on Android.
	if ( platform() === ANDROID_PLATFORM && ! isDismissed ) {
		return (
			<div className="woocommerce-mobile-app-banner">
				<Icon
					icon={ <GridiconCrossIcon data-testid="dismiss-btn" /> }
					onClick={ () => {
						onDismiss();
						setDismissed( true );
						recordEvent( TRACKING_EVENT_NAME, {
							action: 'dismiss',
						} );
					} }
				/>
				<AppIcon />
				<div className="woocommerce-mobile-app-banner__description">
					<p className="woocommerce-mobile-app-banner__description__text">
						{ __(
							'Run your store from anywhere',
							'woocommerce-admin'
						) }
					</p>
					<p className="woocommerce-mobile-app-banner__description__text">
						{ __(
							'Download the WooCommerce app',
							'woocommerce-admin'
						) }
					</p>
				</div>

				<Button
					href={ PLAY_STORE_LINK }
					isSecondary
					onClick={ () => {
						onInstall();
						setDismissed( true );
						recordEvent( TRACKING_EVENT_NAME, {
							action: 'install',
						} );
					} }
				>
					{ __( 'Install', 'woocommerce-admin' ) }
				</Button>
			</div>
		);
	}

	return null;
};
