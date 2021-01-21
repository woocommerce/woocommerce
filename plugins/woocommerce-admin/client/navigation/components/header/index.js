/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { Icon, wordpress } from '@wordpress/icons';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { useSelect } from '@wordpress/data';
import { useEffect } from 'react';
import classnames from 'classnames';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import useIsScrolled from '../../../hooks/useIsScrolled';
import { addHistoryListener } from '../../utils';

const Header = () => {
	const siteTitle = getSetting( 'siteTitle', '' );
	const siteUrl = getSetting( 'siteUrl', '' );
	const isScrolled = useIsScrolled();
	const navClasses = {
		folded: 'is-wc-nav-folded',
		expanded: 'is-wc-nav-expanded',
	};

	const foldNav = () => {
		document.body.classList.add( navClasses.folded );
		document.body.classList.remove( navClasses.expanded );
	};

	const expandNav = () => {
		document.body.classList.remove( navClasses.folded );
		document.body.classList.add( navClasses.expanded );
	};

	const toggleFolded = () => {
		if ( document.body.classList.contains( navClasses.folded ) ) {
			expandNav();
		} else {
			foldNav();
		}
	};

	const foldOnMobile = ( screenWidth = document.body.clientWidth ) => {
		if ( screenWidth <= 960 ) {
			foldNav();
		} else {
			expandNav();
		}
	};

	useEffect( () => {
		foldOnMobile();
		const foldEvents = [
			{
				eventName: 'orientationchange',
				handler: ( e ) => foldOnMobile( e.target.screen.availWidth ),
			},
			{
				eventName: 'resize',
				handler: debounce( () => foldOnMobile(), 200 ),
			},
		];

		for ( const { eventName, handler } of foldEvents ) {
			window.addEventListener( eventName, handler, false );
		}

		addHistoryListener( () => foldOnMobile() );
	}, [] );

	let buttonIcon = <Icon size="36px" icon={ wordpress } />;

	const { isRequestingSiteIcon, siteIconUrl } = useSelect( ( select ) => {
		const { isResolving } = select( 'core/data' );
		const { getEntityRecord } = select( 'core' );
		const siteData =
			getEntityRecord( 'root', '__unstableBase', undefined ) || {};

		return {
			isRequestingSiteIcon: isResolving( 'core', 'getEntityRecord', [
				'root',
				'__unstableBase',
				undefined,
			] ),
			siteIconUrl: siteData.siteIconUrl,
		};
	} );

	if ( siteIconUrl ) {
		buttonIcon = <img alt={ __( 'Site Icon' ) } src={ siteIconUrl } />;
	} else if ( isRequestingSiteIcon ) {
		buttonIcon = null;
	}

	const className = classnames( 'woocommerce-navigation-header', {
		'is-scrolled': isScrolled,
	} );

	return (
		<div className={ className }>
			<Button
				onClick={ () => toggleFolded() }
				className="woocommerce-navigation-header__site-icon"
			>
				{ buttonIcon }
			</Button>
			<Button
				href={ siteUrl }
				className="woocommerce-navigation-header__site-title"
				as="span"
			>
				{ decodeEntities( siteTitle ) }
			</Button>
		</div>
	);
};

export default Header;
