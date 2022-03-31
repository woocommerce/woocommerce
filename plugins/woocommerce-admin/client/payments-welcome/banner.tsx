/**
 * External dependencies
 */
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import strings from './strings';
import FlagIcon from './flag';

const Banner = () => {
	return (
		<Card size="large" className="account-page woocommerce-payments-banner">
			<CardBody>
				<div className="limited-time-offer">
					<h1 className="offer-copy">
						<FlagIcon className="flag-icon" />
						{ strings.limitedTimeOffer }: { strings.bannerCopy }
					</h1>
					<p className="discount-copy">{ strings.discountCopy }</p>
				</div>
				{ /* <h1>{strings.bannerHeading}</h1> */ }
			</CardBody>
		</Card>
	);
};

export default Banner;
