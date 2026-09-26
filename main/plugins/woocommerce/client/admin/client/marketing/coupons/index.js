/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useUser } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './style.scss';
import RecommendedExtensions from './recommended-extensions';
import KnowledgeBase from './knowledge-base';
import { getAdminSetting } from '~/utils/admin-settings';
import Promotions from '~/marketplace/components/promotions/promotions';
import '../data';

const CouponsOverview = () => {
	const { currentUserCan } = useUser();

	const showSuggestions = !! getAdminSetting(
		'allowMarketplaceSuggestions',
		false
	);

	const showExtensions =
		showSuggestions && currentUserCan( 'install_plugins' );

	return (
		<div className="woocommerce-marketing-coupons">
			<Promotions format="promo-card" />
			{ showExtensions && (
				<RecommendedExtensions
					title={ __(
						'Recommended coupon extensions',
						'woocommerce'
					) }
					description={ __(
						'Take your coupon marketing to the next level with our recommended coupon extensions.',
						'woocommerce'
					) }
					category="coupons"
				/>
			) }
			{ showSuggestions && (
				<KnowledgeBase
					category="coupons"
					description={ __(
						'Learn the ins and outs of successful coupon marketing from the experts at WooCommerce.',
						'woocommerce'
					) }
				/>
			) }
		</div>
	);
};

export default CouponsOverview;
