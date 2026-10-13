/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Text } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { ThemeCard, LinkButton } from './theme-card';
import { getMarketplaceThemesUrl } from '../urls';
import athleisure from '../assets/marketplace-athleisure.jpg';
import provar from '../assets/marketplace-provar.jpg';
import nexbyte from '../assets/marketplace-nexbyte.jpg';
import stylezen from '../assets/marketplace-stylezen.jpg';

const THUMBNAILS = [ athleisure, provar, nexbyte, stylezen ];

export const MarketplaceCard = () => (
	<ThemeCard
		className="purple-cys-theme-card--marketplace"
		media={
			<div className="purple-cys-theme-card__thumbnails">
				{ THUMBNAILS.map( ( src ) => (
					<img key={ src } src={ src } alt="" />
				) ) }
			</div>
		}
	>
		<Text variant="heading-lg" render={ <h2 /> }>
			{ __( 'Browse the Marketplace', 'woocommerce' ) }
		</Text>
		<Text className="purple-cys-theme-card__description" render={ <p /> }>
			{ __(
				'Explore hundreds of free and paid themes made for WooCommerce stores, with ready-to-use designs for every industry. Paid themes come with a 30-day money-back guarantee.',
				'woocommerce'
			) }
		</Text>
		<div className="purple-cys-theme-card__actions">
			<LinkButton variant="outline" href={ getMarketplaceThemesUrl() }>
				{ __( 'Browse themes', 'woocommerce' ) }
			</LinkButton>
		</div>
	</ThemeCard>
);
