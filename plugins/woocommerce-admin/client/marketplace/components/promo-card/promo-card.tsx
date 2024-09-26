/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './promo-card.scss';
import { Promotion } from '../promotions/types';

interface PromoCardProps {
	promotion: Promotion;
}

const PromoCard: React.FC< PromoCardProps > = ( { promotion } ) => {
	return (
		<div className="promo-card">
			<h2 className="promo-title">{ promotion.title?.en_US }</h2>
			<p
				className="promo-text"
				dangerouslySetInnerHTML={ { __html: promotion.content.en_US } } // Render HTML in the text
			></p>
			<div className="promo-links">
				<Link className="promo-cta-dismiss" href="#">
					{ __( 'Dismiss', 'woocommerce' ) }
				</Link>
				<Link className="promo-cta" href={ promotion.cta_link ?? '' }>
					{ promotion.cta_label?.en_US }
				</Link>
			</div>
		</div>
	);
};

export default PromoCard;
