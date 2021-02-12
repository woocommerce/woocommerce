/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import { useUserPreferences } from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanel from './activity-panel';
import { MobileAppBanner } from '../mobile-banner';
import useIsScrolled from '../hooks/useIsScrolled';
import Navigation from '../navigation';

export const Header = ( { sections, isEmbedded = false, query } ) => {
	const headerElement = useRef( null );
	const siteTitle = getSetting( 'siteTitle', '' );
	const pageTitle = sections.slice( -1 )[ 0 ];
	const isScrolled = useIsScrolled();
	const { updateUserPreferences, ...userData } = useUserPreferences();
	const isModalDismissed = userData.android_app_banner_dismissed === 'yes';
	let debounceTimer = null;

	const className = classnames( 'woocommerce-layout__header', {
		'is-scrolled': isScrolled,
	} );

	useLayoutEffect( () => {
		updateBodyMargin();
		window.addEventListener( 'resize', updateBodyMargin );
		return () => {
			window.removeEventListener( 'resize', updateBodyMargin );
			const wpBody = document.querySelector( '#wpbody' );

			if ( ! wpBody ) {
				return;
			}

			wpBody.style.marginTop = null;
		};
	}, [ isModalDismissed ] );

	const updateBodyMargin = () => {
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( function () {
			const wpBody = document.querySelector( '#wpbody' );

			if ( ! wpBody ) {
				return;
			}

			wpBody.style.marginTop = `${ headerElement.current.offsetHeight }px`;
		}, 200 );
	};

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

			<div className="woocommerce-layout__header-wrapper">
				{ window.wcAdminFeatures.navigation && <Navigation /> }

				<Text
					className="woocommerce-layout__header-heading"
					as="h1"
					variant="subtitle.small"
				>
					{ decodeEntities( pageTitle ) }
				</Text>

				{ window.wcAdminFeatures[ 'activity-panels' ] && (
					<ActivityPanel
						isEmbedded={ isEmbedded }
						query={ query }
						userPreferencesData={ {
							...userData,
							updateUserPreferences,
						} }
					/>
				) }
			</div>
		</div>
	);
};
