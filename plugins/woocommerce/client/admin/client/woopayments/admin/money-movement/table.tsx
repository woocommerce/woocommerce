/**
 * External dependencies
 */
import type { ReactNode } from 'react';

export const StatusMessage = ( {
	isError = false,
	children,
}: {
	isError?: boolean;
	children: ReactNode;
} ) => (
	<p
		className={
			isError
				? 'woocommerce-woopayments-money-movement__status is-error'
				: 'woocommerce-woopayments-money-movement__status'
		}
	>
		{ children }
	</p>
);

export const LiveStatusMessage = ( {
	isError = false,
	children,
}: {
	isError?: boolean;
	children: ReactNode;
} ) => (
	<p
		className="screen-reader-text"
		role={ isError ? 'alert' : 'status' }
		aria-live={ isError ? 'assertive' : 'polite' }
		aria-atomic="true"
	>
		{ children }
	</p>
);

export const EmptyState = ( { children }: { children: ReactNode } ) => (
	<div className="woocommerce-woopayments-money-movement__empty">
		{ children }
	</div>
);
