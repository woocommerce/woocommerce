/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import WCSImage from '../images/wcs.svg';
import PrinterImage from '../images/printer.svg';
import PaperImage from '../images/paper.svg';
import DiscountImage from '../images/discount.svg';
import './wcs-banner.scss';

export const WCSBanner = () => {
	return (
		<div className="woocommerce-task-shipping-recommendation__plugins-install">
			<div className="plugins-install__wcs-image">
				<img src={ WCSImage } alt="" />
			</div>
			<div className="plugins-install__list">
				<div className="plugins-install__list-item">
					<div className="plugins-install__list-icon">
						<img src={ PrinterImage } alt="" />
					</div>
					<div>
						<div>
							<strong>
								{ __(
									'Buy postage when you need it',
									'woocommerce'
								) }
							</strong>
						</div>
						<div>
							{ __(
								'No need to wonder where that stampbook went.',
								'woocommerce'
							) }
						</div>
					</div>
				</div>
				<div className="plugins-install__list-item">
					<div className="plugins-install__list-icon">
						<img src={ PaperImage } alt="" />
					</div>
					<div>
						<div>
							<strong>
								{ __( 'Print at home', 'woocommerce' ) }
							</strong>
						</div>
						<div>
							{ __(
								'Pick up an order, then just pay, print, package and post.',
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
								{ __( 'Discounted rates', 'woocommerce' ) }
							</strong>
						</div>
						<div>
							{ __(
								'Access discounted shipping rates with DHL and USPS.',
								'woocommerce'
							) }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};
