/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { WCPaymentsRow } from './wc-payments-row';

const container = document.querySelector(
	'[data-gateway_id="pre_install_woocommerce_payments_promotion"]'
);

if ( container ) {
	const sortColumn = container.children[ 0 ].innerHTML;
	const descriptionColumn = container.children[ 3 ].innerHTML;
	render(
		<WCPaymentsRow
			sortColumnContent={ sortColumn }
			descriptionColumnContent={ descriptionColumn }
		/>,
		container
	);
}
