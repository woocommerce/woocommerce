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
import { PLAY_STORE_LINK, TRACKING_EVENT_NAME } from './constants';
import './banner.scss';

const SHOW_APP_BANNER_MODIFIER_CLASS = 'woocommerce-layout__show-app-banner';

export const Banner = ( { onInstall, onDismiss } ) => {
	const [ isActioned, setIsActioned ] = useState( false );
	const isVisible = platform() === ANDROID_PLATFORM && ! isActioned;

	useEffect( () => {
		const layout =
			document.getElementsByClassName( 'woocommerce-layout' )[ 0 ];

		if ( isVisible && layout ) {
			// This is a hack to allow the mobile banner to work in the context of the header which is
			// position fixed. This can be refactored away when we move away from the activity panel
			// in future.
			layout.classList.add( SHOW_APP_BANNER_MODIFIER_CLASS );
		}

		return () => {
			if ( layout ) {
				layout.classList.remove( SHOW_APP_BANNER_MODIFIER_CLASS );
			}
		};
	}, [ isVisible ] );

	if ( ! isVisible ) {
		return null;
	}

	return (
		<div className="woocommerce-mobile-app-banner">
			<Icon
				icon={ <GridiconCrossIcon data-testid="dismiss-btn" /> }
				onClick={ () => {
					onDismiss();
					setIsActioned( true );
					recordEvent( TRACKING_EVENT_NAME, {
						action: 'dismiss',
					} );
				} }
			/>
			<AppIcon />
			<div className="woocommerce-mobile-app-banner__description">
				<p className="woocommerce-mobile-app-banner__description__text">
					{ __( 'Run your store from anywhere', 'woocommerce' ) }
				</p>
				<p className="woocommerce-mobile-app-banner__description__text">
					{ __( 'Download the WooCommerce app', 'woocommerce' ) }
				</p>
			</div>

			<Button
				href={ PLAY_STORE_LINK }
				isSecondary
				onClick={ () => {
					onInstall();
					setIsActioned( true );
					recordEvent( TRACKING_EVENT_NAME, {
						action: 'install',
					} );
				} }
			>
				{ __( 'Install', 'woocommerce' ) }
			</Button>
		</div>
	);
};
