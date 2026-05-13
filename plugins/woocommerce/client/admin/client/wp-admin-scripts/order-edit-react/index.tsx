/**
 * Entry point for the order-edit-react experiment.
 *
 * Mounts a React root into `#wc-react-order-edit-root` (emitted server-side
 * by Edit::display_react when the `order-detail-design-system-comp` feature flag is on).
 */

import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { App } from './app';
import { OrderActionsMenu } from './components/order-actions-menu';
import './style.scss';

domReady( () => {
	const mount = document.getElementById( 'wc-react-order-edit-root' );
	if ( ! mount ) {
		return;
	}
	const orderIdAttr = mount.getAttribute( 'data-order-id' );
	const orderId = orderIdAttr ? parseInt( orderIdAttr, 10 ) : NaN;
	if ( ! orderId || Number.isNaN( orderId ) ) {
		return;
	}

	createRoot( mount ).render( <App orderId={ orderId } /> );

	// Mount the H1-row "More actions" kebab into its server-emitted slot.
	// Snackbars from this menu bubble through the window event and are caught
	// by the SnackbarHost rendered inside <App>.
	const actionsMount = document.getElementById( 'wc-react-order-edit-actions-menu' );
	if ( actionsMount ) {
		const actionsOrderId = parseInt(
			actionsMount.getAttribute( 'data-order-id' ) || '0',
			10
		);
		createRoot( actionsMount ).render(
			<OrderActionsMenu orderId={ actionsOrderId } />
		);
	}
} );
