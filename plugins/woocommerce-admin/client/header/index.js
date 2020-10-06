/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import { useUserPreferences } from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { __experimentalText as Text } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanel from './activity-panel';
import { MobileAppBanner } from '../mobile-banner';

export const Header = ( { sections, isEmbedded = false, query } ) => {
	const headerElement = useRef( null );
	const rafHandle = useRef( null );
	const threshold = useRef( null );
	const siteTitle = getSetting( 'siteTitle', '' );
	const pageTitle = sections.slice( -1 )[ 0 ];
	const [ isScrolled, setIsScrolled ] = useState( false );
	const { updateUserPreferences, ...userData } = useUserPreferences();

	const isModalDismissed = userData.android_app_banner_dismissed === 'yes';

	const className = classnames( 'woocommerce-layout__header', {
		'is-scrolled': isScrolled,
	} );

	useEffect( () => {
		threshold.current = headerElement.current.offsetTop;

		const updateIsScrolled = () => {
			setIsScrolled( window.pageYOffset > threshold.current - 20 );
		};

		const scrollListener = () => {
			rafHandle.current = window.requestAnimationFrame(
				updateIsScrolled
			);
		};

		window.addEventListener( 'scroll', scrollListener );

		return () => {
			window.removeEventListener( 'scroll', scrollListener );
			window.cancelAnimationFrame( rafHandle.current );
		};
	}, [] );

	useEffect( () => {
		if ( ! isEmbedded ) {
			const documentTitle = sections
				.map( ( section ) => {
					return Array.isArray( section ) ? section[ 1 ] : section;
				} )
				.reverse()
				.join( ' &lsaquo; ' );

			const decodedTitle = decodeEntities(
				sprintf(
					/* translators: 1: document title. 2: page title */
					__(
						'%1$s &lsaquo; %2$s &#8212; WooCommerce',
						'woocommerce-admin'
					),
					documentTitle,
					siteTitle
				)
			);

			if ( document.title !== decodedTitle ) {
				document.title = decodedTitle;
			}
		}
	}, [ isEmbedded, sections, siteTitle ] );

	const dismissHandler = () => {
		updateUserPreferences( {
			android_app_banner_dismissed: 'yes',
		} );
	};

	return (
		<div className={ className } ref={ headerElement }>
			{ ! isModalDismissed && (
				<MobileAppBanner
					onDismiss={ dismissHandler }
					onInstall={ dismissHandler }
				/>
			) }

			<Text
				className="woocommerce-layout__header-heading"
				as="h1"
				variant="subtitle.small"
			>
				{ decodeEntities( pageTitle ) }
			</Text>
			{ window.wcAdminFeatures[ 'activity-panels' ] && (
				<ActivityPanel isEmbedded={ isEmbedded } query={ query } />
			) }
		</div>
	);
};
