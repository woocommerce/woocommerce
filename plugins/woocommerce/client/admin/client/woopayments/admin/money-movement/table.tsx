/**
 * External dependencies
 */
import type { HTMLAttributes, ReactNode } from 'react';

const getLiveStatusAttributes = ( {
	isError,
	isLive,
}: {
	isError: boolean;
	isLive: boolean;
} ): Pick<
	HTMLAttributes< HTMLParagraphElement >,
	'aria-atomic' | 'aria-live' | 'role'
> => {
	if ( ! isLive ) {
		return {};
	}

	return {
		role: isError ? 'alert' : 'status',
		'aria-live': isError ? 'assertive' : 'polite',
		'aria-atomic': true,
	};
};

export const StatusMessage = ( {
	isError = false,
	isLive = false,
	children,
}: {
	isError?: boolean;
	isLive?: boolean;
	children: ReactNode;
} ) => (
	<p
		{ ...getLiveStatusAttributes( { isError, isLive } ) }
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
