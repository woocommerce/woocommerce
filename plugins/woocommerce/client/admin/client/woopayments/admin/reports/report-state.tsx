/**
 * External dependencies
 */
import type { ReactNode, RefObject } from 'react';

type ReportStateProps = {
	title: string;
	description: ReactNode;
	action?: ReactNode;
	role?: 'status' | 'alert';
	headingRef?: RefObject< HTMLHeadingElement >;
	headingTabIndex?: number;
	className?: string;
};

export const ReportState = ( {
	title,
	description,
	action,
	role = 'status',
	headingRef,
	headingTabIndex,
	className = '',
}: ReportStateProps ) => (
	<section
		role={ role }
		className={ `woocommerce-woopayments-reports-state ${ className }` }
	>
		<h2 ref={ headingRef } tabIndex={ headingTabIndex }>
			{ title }
		</h2>
		<div className="woocommerce-woopayments-reports-state__description">
			{ description }
		</div>
		{ action && (
			<div className="woocommerce-woopayments-reports-state__action">
				{ action }
			</div>
		) }
	</section>
);
