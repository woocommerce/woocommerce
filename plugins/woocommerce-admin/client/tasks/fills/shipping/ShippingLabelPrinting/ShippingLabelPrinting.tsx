/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PrintIcon from 'gridicons/dist/print';
import PagesIcon from 'gridicons/dist/pages';

/**
 * Internal dependencies
 */
import BannerImage from './banner.svg';

export const ShippingLabelPrinting = ( {} ) => {
	return (
		<div className="woocommerce-task-shipping-shipping-label-printing">
			<div className="shipping-label-printing-row">
				<div className="banner shipping-label-printing-col">
					<img src={ BannerImage } alt="woocommerce shipping" />
				</div>
				<div className="content shipping-label-printing-col">
					<div className="row">
						<div className="col col-left">
							<div>
								<PrintIcon />
							</div>
						</div>
						<div className="col">
							<div>
								{ __(
									'Buy postage when you need it',
									'woocommerce'
								) }
							</div>
							<div>
								{ __(
									'No need to wonder where that stampbook went',
									'woocommerce'
								) }
								.
							</div>
						</div>
					</div>
					<div className="row">
						<div className="col col-left">
							<div>
								<PagesIcon />
							</div>
						</div>
						<div className="col">
							<div>{ __( 'Print at home', 'woocommerce' ) }</div>
							<div>
								{ __(
									'Pick up an order, then just pay, print, package and post.',
									'woocommerce'
								) }
							</div>
						</div>
					</div>
					<div className="row">
						<div className="col col-left">
							<div className="icon-percentage">%</div>
						</div>
						<div className="col">
							<div>
								{ __( 'Discounted rates', 'woocommerce' ) }
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
		</div>
	);
};
