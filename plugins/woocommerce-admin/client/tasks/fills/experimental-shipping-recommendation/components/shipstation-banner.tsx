/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ShipStationImage from '../images/shipstation.svg';
import TimerImage from '../images/timer.svg';
import StarImage from '../images/star.svg';
import DiscountImage from '../images/discount.svg';
import './wcs-banner.scss';

export const ShipStationBanner = () => {
	return (
		<div className="woocommerce-task-shipping-recommendation__plugins-install">
			<div className="plugins-install__shipstation-image">
				<img src={ ShipStationImage } alt="" />
			</div>
			<div className="plugins-install__list">
				<div className="plugins-install__list-item">
					<div className="plugins-install__list-icon">
						<img src={ TimerImage } alt="" />
					</div>
					<div>
						<div>
							<strong>
								{ __( 'Save your time', 'woocommerce' ) }
							</strong>
						</div>
						<div>
							{ __(
								'Import your orders automatically, no matter where you sell.',
								'woocommerce'
							) }
						</div>
					</div>
				</div>
				<div className="plugins-install__list-item">
					<div className="plugins-install__list-icon">
						<img src={ DiscountImage } alt="" />
					</div>
					<div>
						<div>
							<strong>
								{ __( 'Save your money', 'woocommerce' ) }
							</strong>
						</div>
						<div>
							{ __(
								'Live shipping rates amongst all the carrier choices.',
								'woocommerce'
							) }
						</div>
					</div>
				</div>
				<div className="plugins-install__list-item">
					<div className="plugins-install__list-icon">
						<img src={ StarImage } alt="" />
					</div>
					<div>
						<div>
							<strong>
								{ __( 'Wow your shoppers', 'woocommerce' ) }
							</strong>
						</div>
						<div>
							{ __(
								'Customize notification emails, packing slips, shipping labels.',
								'woocommerce'
							) }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};
