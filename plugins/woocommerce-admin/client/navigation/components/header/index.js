/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
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

const Header = () => {
	const siteTitle = getSetting( 'siteTitle', '' );
	const siteUrl = getSetting( 'siteUrl', '' );
	const isScrolled = useIsScrolled();

	const toggleFolded = () => {
		document.body.classList.toggle( 'is-folded' );
	};

	const foldOnMobile = ( screenWidth = document.body.clientWidth ) => {
		const isSmallScreen = screenWidth <= 960;

		document.body.classList[ isSmallScreen ? 'add' : 'remove' ](
			'is-folded'
		);
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
				{ siteTitle }
			</Button>
		</div>
	);
};

export default Header;
