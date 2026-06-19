/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsOverviewAccountDetails,
	WooPaymentsOverviewAccountFee,
} from '../types';

const getFeeLabel = ( fee: WooPaymentsOverviewAccountFee ) =>
	fee.label ||
	fee.payment_method
		.replace( /_/g, ' ' )
		.replace( /^\w/, ( match ) => match.toUpperCase() );

export const AccountDetailsCard = ( {
	accountDetails,
	accountFees = [],
}: {
	accountDetails?: WooPaymentsOverviewAccountDetails | null;
	accountFees?: WooPaymentsOverviewAccountFee[];
} ) => {
	if ( ! accountDetails ) {
		return null;
	}

	return (
		<section className="woocommerce-woopayments-overview-card woocommerce-woopayments-account-details">
			<h2 tabIndex={ -1 }>{ __( 'Account details', 'woocommerce' ) }</h2>
			<div className="woocommerce-woopayments-account-details__status-grid">
				<div>
					<h3>{ __( 'Account status', 'woocommerce' ) }</h3>
					<p>{ accountDetails.account_status.text || '-' }</p>
				</div>
				<div>
					<h3>{ __( 'Payout status', 'woocommerce' ) }</h3>
					<p>{ accountDetails.payout_status.text || '-' }</p>
				</div>
			</div>
			{ accountDetails.banner?.text && (
				<div className="woocommerce-woopayments-account-details__banner">
					<p>{ accountDetails.banner.text }</p>
					{ accountDetails.banner.cta_link &&
						accountDetails.banner.cta_text && (
							<a href={ accountDetails.banner.cta_link }>
								{ accountDetails.banner.cta_text }
							</a>
						) }
				</div>
			) }
			{ accountFees.length > 0 && (
				<div className="woocommerce-woopayments-account-details__fees">
					<h3>{ __( 'Payment method fees', 'woocommerce' ) }</h3>
					<ul>
						{ accountFees.map( ( fee ) => (
							<li key={ fee.payment_method }>
								{ getFeeLabel( fee ) }
							</li>
						) ) }
					</ul>
				</div>
			) }
		</section>
	);
};
