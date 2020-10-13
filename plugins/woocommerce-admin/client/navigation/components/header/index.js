/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Icon, wordpress } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { NAVIGATION_STORE_NAME } from '../../store';

const Header = () => {
	const toggleFolded = () => {
		document.body.classList.toggle( 'is-folded' );
	};

	let buttonIcon = <Icon size="36px" icon={ wordpress } />;

	const { isRequestingSiteIcon, siteIconUrl, siteTitle, siteUrl } = useSelect(
		( select ) => {
			const { getSiteTitle, getSiteUrl } = select(
				NAVIGATION_STORE_NAME
			);
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
				siteTitle: getSiteTitle(),
				siteUrl: getSiteUrl(),
			};
		}
	);

	if ( siteIconUrl ) {
		buttonIcon = <img alt={ __( 'Site Icon' ) } src={ siteIconUrl } />;
	} else if ( isRequestingSiteIcon ) {
		buttonIcon = null;
	}

	return (
		<div className="woocommerce-navigation-header">
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
