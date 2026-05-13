/**
 * Inline "Customer will be notified" indicator shown on email-firing affordances
 * (status dropdown values, Refund / Mark-as-paid buttons, the "To customer"
 * note toggle in the composer).
 *
 * Per the v1 spec: no rendered email content. Just transparency + the suppress
 * toggle inside the action's modal.
 */

import { __ } from '@wordpress/i18n';

interface EmailIndicatorProps {
	/** Compact form: a single icon + sr-only label. Inline form: icon + visible text. */
	compact?: boolean;
}

export function EmailIndicator( { compact = false }: EmailIndicatorProps ) {
	const label = __( 'Customer will be notified', 'woocommerce' );

	return (
		<span className="wc-react-order-edit__email-indicator" aria-label={ label } title={ label }>
			<span aria-hidden="true">✉</span>
			{ ! compact && (
				<span className="wc-react-order-edit__email-indicator-text">{ label }</span>
			) }
		</span>
	);
}
