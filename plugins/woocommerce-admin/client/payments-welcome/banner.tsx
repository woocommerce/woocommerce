/**
 * External dependencies
 */
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import strings from './strings';
import GiftIcon from './gift';

const Banner = () => {
	return (
		<Card size="large" className="account-page woocommerce-payments-banner">
			<CardBody>
				<div className="limited-time-offer">
					<div className="offer-header">
						<GiftIcon className="gift-icon" />
						{ strings.limitedTimeOffer }
					</div>
					<h1 className="offer-copy">{ strings.bannerCopy }</h1>
					<p>{ strings.discountCopy }</p>
					<p>{ strings.termsAndConditions }</p>
				</div>
			</CardBody>
		</Card>
	);
};

export default Banner;
