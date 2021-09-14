/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import { useUserPreferences } from '@woocommerce/data';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { Text } from '@woocommerce/experimental';
import { Icon, chevronLeft } from '@wordpress/icons';
import { getHistory, updateQueryString } from '@woocommerce/navigation';
import { ENTER, SPACE } from '@wordpress/keycodes';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanel from './activity-panel';
import { MobileAppBanner } from '../mobile-banner';
import useIsScrolled from '../hooks/useIsScrolled';
import Navigation from '../navigation';

const renderTaskListBackButton = () => {
	const currentUrl = new URL( window.location.href );
	const task = currentUrl.searchParams.get( 'task' );

	if ( task ) {
		const homeText = __( 'WooCommerce Home', 'woocommerce-admin' );

		const navigateHome = () => {
			recordEvent( 'topbar_back_button', {
				page_name: getPageTitle( window.title ),
			} );
			updateQueryString( {}, getHistory().location.pathname, {} );
		};

		// if it's a task list page, render a back button to the homescreen
		return (
			<Tooltip text={ homeText }>
				<div
					tabIndex="0"
					role="button"
					data-testid="header-back-button"
					className="woocommerce-layout__header-back-button"
					onKeyDown={ ( { keyCode } ) => {
						if ( keyCode === ENTER || keyCode === SPACE ) {
							navigateHome();
						}
					} }
				>
					<Icon icon={ chevronLeft } onClick={ navigateHome } />
				</div>
			</Tooltip>
		);
	}

	return null;
};

const getPageTitle = ( defaultTitle ) => {
	const currentUrl = new URL( window.location.href );
	const task = currentUrl.searchParams.get( 'task' );

	// If it's the task list then render a title based on which task the user is on.
	return (
		{
			payments: __( 'Set up payments', 'woocommerce-admin' ),
			tax: __( 'Add tax rates', 'woocommerce-admin' ),
			appearance: __( 'Personalize your store', 'woocommerce-admin' ),
			marketing: __( 'Set up marketing tools', 'woocommerce-admin' ),
			products: __( 'Add products', 'woocommerce-admin' ),
			shipping: __( 'Set up shipping costs', 'woocommerce-admin' ),
		}[ task ] || defaultTitle
	);
};

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

			if ( ! wpBody || ! headerElement.current ) {
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

	const backButton = renderTaskListBackButton();
	const backButtonClass = backButton ? 'with-back-button' : '';

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
				{ renderTaskListBackButton() }
				<Text
					className={ `woocommerce-layout__header-heading ${ backButtonClass }` }
					as="h1"
				>
					{ getPageTitle( decodeEntities( pageTitle ) ) }
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
