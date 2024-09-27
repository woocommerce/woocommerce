/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './promo-card.scss';
import { Promotion } from '../promotions/types';

interface PromoCardProps {
	promotion: Promotion;
}

const PromoCard: React.FC< PromoCardProps > = ( { promotion } ) => {
	const { id } = promotion;

	const [ isVisible, setIsVisible ] = useState(
		localStorage.getItem( `wc-marketplacePromoClosed-${ id }` ) !== 'true'
	);

	if ( ! isVisible ) return null;

	const uri = window.location.pathname + window.location.search;

	recordEvent( 'marketplace_promotion_viewed', {
		id,
		uri,
	} );

	const handleDismiss = () => {
		setIsVisible( false );
		localStorage.setItem( `wc-marketplacePromoClosed-${ id }`, 'true' );

		recordEvent( 'marketplace_promotion_dismissed', {
			id,
			uri,
		} );
	};

	const handleClick = () => {
		recordEvent( 'marketplace_promotion_actioned', {
			id,
			uri,
			target_uri: promotion.cta_link,
		} );

		return true;
	};

	return (
		<div className="promo-card">
			<h2 className="promo-title">{ promotion.title?.en_US }</h2>
			<p
				className="promo-text"
				dangerouslySetInnerHTML={ { __html: promotion.content.en_US } } // Render HTML in the text
			></p>
			<div className="promo-links">
				<Button className="promo-cta-dismiss" onClick={ handleDismiss }>
					{ __( 'Dismiss', 'woocommerce' ) }
				</Button>
				<Button
					className="promo-cta"
					href={ promotion.cta_link ?? '' }
					onClick={ handleClick }
				>
					{ promotion.cta_label?.en_US }
				</Button>
			</div>
		</div>
	);
};

export default PromoCard;
