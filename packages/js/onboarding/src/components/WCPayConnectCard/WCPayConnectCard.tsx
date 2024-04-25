/**
 * External dependencies
 */
import { Card } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import WCPayLogo from '../../images/wcpay-logo';
import { PaymentMethodsIcons } from './PaymentMethodsIcons';
import TipBox from './TipBox';

export const WCPayConnectCard: React.VFC< {
	className?: string;
	actionButton: React.ReactNode;
	firstName?: string;
	businessCountry?: string;
	isWooPayEligible: boolean;
	showNotice?: boolean;
} > = ( {
	className = '',
	actionButton,
	firstName = '',
	businessCountry = '',
	isWooPayEligible,
	showNotice = false,
} ) => {
	return (
		<Card className={ classNames( 'wcpay-connect-card', className ) }>
			<div className="wcpay-connect-card__heading">
				<WCPayLogo />
				<h2>
					{ sprintf(
						/* translators: %1$s: first name of the merchant, if it exists, %2$s: WooPayments. */
						__( 'Hi%1$s, Welcome to %2$s!', 'woocommerce' ),
						firstName ? ` ${ firstName }` : '',
						'WooPayments'
					) }
				</h2>
			</div>
			{ showNotice && (
				<div className="wcpay-connect-card__content">
					<TipBox color="yellow">
						{ __(
							'Payments made simple, with no monthly fees – designed exclusively for WooCommerce stores.',
							'woocommerce'
						) }
					</TipBox>
				</div>
			) }
			<div className="wcpay-connect-card__payment-methods">
				<PaymentMethodsIcons
					businessCountry={ businessCountry ?? '' }
					isWooPayEligible={ isWooPayEligible }
				/>
				<div className="wcpay-connect-card__payment-methods__description">
					<div>
						<p>{ __( 'Deposits', 'woocommerce' ) }</p>
						<span>
							{ __( 'Automatic - Daily', 'woocommerce' ) }
						</span>
					</div>
					<div className="wcpay-connect-card__payment-methods__description__divider"></div>
					<div>
						<p>{ __( 'Payments capture', 'woocommerce' ) }</p>
						<span>{ __( 'Capture on order', 'woocommerce' ) }</span>
					</div>
					<div className="wcpay-connect-card__payment-methods__description__divider"></div>
					<div>
						<p>{ __( 'Recurring payments', 'woocommerce' ) }</p>
						<span>{ __( 'Supported', 'woocommerce' ) }</span>
					</div>
				</div>
			</div>
			<div className="wcpay-connect-card__buttons">{ actionButton }</div>
		</Card>
	);
};
