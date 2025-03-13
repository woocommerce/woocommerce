/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import { PaymentIncentive } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { StatusBadge } from '~/settings-payments/components/status-badge';
import './incentive-status-badge.scss';

interface IncentiveStatusBadgeProps {
	/**
	 * The payment incentive used to generate the status badge.
	 */
	incentive: PaymentIncentive;
}

/**
 * A helper component that accepts a PaymentIncentive and renders the appropriate
 * StatusBadge component.
 *
 * @example
 * // Render an incentive status badge which displays a popover.
 * <IncentiveStatusBadge incentive={ incentive } />
 */
export const IncentiveStatusBadge = ( {
	incentive,
}: IncentiveStatusBadgeProps ) => {
	return (
		<StatusBadge
			status={ 'has_incentive' }
			message={ incentive.badge }
			popoverContent={
				<>
					<p className={ 'woocommerce-incentive-popover__title' }>
						{ incentive.title }
					</p>
					<p className={ 'woocommerce-incentive-popover__terms' }>
						{ createInterpolateElement(
							__(
								'See <termsLink /> for details.',
								'woocommerce'
							),
							{
								termsLink: (
									<Link
										href={ incentive.tc_url }
										target="_blank"
										rel="noreferrer"
										type="external"
									>
										{ __(
											'Terms and Conditions',
											'woocommerce'
										) }
									</Link>
								),
							}
						) }
					</p>
				</>
			}
		/>
	);
};
