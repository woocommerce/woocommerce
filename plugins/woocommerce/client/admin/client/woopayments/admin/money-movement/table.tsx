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
		className="woocommerce-woopayments-money-movement__status"
		role={ isError ? 'alert' : 'status' }
		aria-live={ isError ? 'assertive' : 'polite' }
	>
		{ children }
	</p>
);

export const EmptyState = ( { children }: { children: ReactNode } ) => (
	<div className="woocommerce-woopayments-money-movement__empty">
		{ children }
	</div>
);
