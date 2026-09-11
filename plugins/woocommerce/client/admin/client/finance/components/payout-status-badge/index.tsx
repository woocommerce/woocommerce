/**
 * External dependencies
 */
import { Pill } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import {
	getPayoutStatusLabel,
	getPayoutStatusVariant,
} from '../../utils/payout-status';
import './style.scss';

type PayoutStatusBadgeProps = {
	status: string;
};

export const PayoutStatusBadge = ( { status }: PayoutStatusBadgeProps ) => (
	<Pill
		className={ `woocommerce-finance-status-badge woocommerce-finance-status-badge--${ getPayoutStatusVariant(
			status
		) }` }
	>
		{ getPayoutStatusLabel( status ) }
	</Pill>
);
