/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import classnames from 'classnames';
import { decodeEntities } from '@wordpress/html-entities';
import { Link } from '@woocommerce/components';
import { useUserPreferences } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import './style.scss';
import ActivityPanel from './activity-panel';
import { MobileAppBanner } from '../mobile-banner';

const trackLinkClick = ( event ) => {
	const target = event.target.closest( 'a' );
	const href = target.getAttribute( 'href' );

	if ( href ) {
		recordEvent( 'navbar_breadcrumb_click', {
			href,
			text: target.innerText,
		} );
	}
};

export const Header = ( { sections, isEmbedded = false, query } ) => {
	const headerElement = useRef( null );
	const rafHandle = useRef( null );
	const threshold = useRef( null );
	const siteTitle = getSetting( 'siteTitle', '' );
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
			<h1 className="woocommerce-layout__header-breadcrumbs">
				{ sections.map( ( section, i ) => {
					const sectionPiece = Array.isArray( section ) ? (
						<Link
							href={
								isEmbedded
									? getAdminLink( section[ 0 ] )
									: getNewPath( {}, section[ 0 ], {} )
							}
							type={ isEmbedded ? 'wp-admin' : 'wc-admin' }
							onClick={ trackLinkClick }
						>
							{ section[ 1 ] }
						</Link>
					) : (
						section
					);
					return (
						<span key={ i }>
							{ decodeEntities( sectionPiece ) }
						</span>
					);
				} ) }
			</h1>
			{ window.wcAdminFeatures[ 'activity-panels' ] && (
				<ActivityPanel isEmbedded={ isEmbedded } query={ query } />
			) }
		</div>
	);
};
