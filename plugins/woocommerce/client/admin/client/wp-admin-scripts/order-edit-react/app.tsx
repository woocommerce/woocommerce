/**
 * Top-level App for the order-edit-react experiment.
 *
 * Owns:
 *   - Initial data fetch (order + statuses).
 *   - Provider composition (OrderProvider + NotesProvider).
 *   - 2-column layout: main column (Customer, Notes, Items & totals, Custom fields,
 *     legacy extensions zone) + side column (Status, History, side metaboxes).
 *   - Snackbar host listening for app-wide save / delete events.
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, Notice } from '@wordpress/components';
import { OrderProvider, useOrder } from './data/order-context';
import { NotesProvider } from './data/notes-context';
import { fetchOrderStatuses } from './data/api';
import type { OrderStatusOption } from './data/types';
import { OrderStatusPanel } from './components/order-status-panel';
import { CustomerPanel } from './components/customer-panel';
import { NotesPanel } from './components/notes-panel';
import { ItemsTotalsPanel } from './components/items-totals-panel';
import { CustomFieldsPanel } from './components/custom-fields-panel';
import { OrderAttributionPanel } from './components/order-attribution-panel';
import { HistoryTimeline } from './components/history-timeline';
import { LegacyMount } from './components/legacy-mount';
import { SnackbarHost } from './components/snackbar-host';
import { ErrorBoundary } from './components/error-boundary';

interface AppProps {
	orderId: number;
}

export function App( { orderId }: AppProps ) {
	const [ statuses, setStatuses ] = useState< OrderStatusOption[] >( [] );
	const [ statusesError, setStatusesError ] = useState< string | null >( null );

	useEffect( () => {
		let cancelled = false;
		fetchOrderStatuses()
			.then( ( s ) => {
				if ( ! cancelled ) {
					setStatuses( s );
				}
			} )
			.catch( ( err: unknown ) => {
				if ( ! cancelled ) {
					setStatusesError(
						err instanceof Error ? err.message : String( err )
					);
				}
			} );
		return () => {
			cancelled = true;
		};
	}, [] );

	return (
		<OrderProvider orderId={ orderId }>
			<NotesProvider orderId={ orderId }>
				<AppShell statuses={ statuses } statusesError={ statusesError } />
				<SnackbarHost />
			</NotesProvider>
		</OrderProvider>
	);
}

interface AppShellProps {
	statuses: OrderStatusOption[];
	statusesError: string | null;
}

function AppShell( { statuses, statusesError }: AppShellProps ) {
	const { loading, error } = useOrder();

	return (
		<div className="wc-react-order-edit__app">
			{ statusesError && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Could not load order statuses: ', 'woocommerce' ) }
					{ statusesError }
				</Notice>
			) }

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ __( 'Could not load order: ', 'woocommerce' ) }
					{ error }
				</Notice>
			) }

			{ loading && (
				<div className="wc-react-order-edit__loading-wrap" aria-live="polite">
					<Spinner />
					<span>{ __( 'Loading order…', 'woocommerce' ) }</span>
				</div>
			) }

			<div className="wc-react-order-edit__layout">
				<main className="wc-react-order-edit__main-col">
					<ErrorBoundary label="Items & totals panel">
						<ItemsTotalsPanel />
					</ErrorBoundary>
					<ErrorBoundary label="Customer panel">
						<CustomerPanel />
					</ErrorBoundary>
					<ErrorBoundary label="Custom fields panel">
						<CustomFieldsPanel />
					</ErrorBoundary>
					<details className="wc-react-order-edit__extensions-details">
						<summary>{ __( 'Extensions (legacy)', 'woocommerce' ) }</summary>
						<LegacyMount
							templateId="wc-react-order-edit-tmpl-extensions"
							className="wc-react-order-edit__legacy-extensions"
						/>
					</details>
				</main>
				<aside className="wc-react-order-edit__side-col">
					<ErrorBoundary label="Status panel">
						<OrderStatusPanel statuses={ statuses } />
					</ErrorBoundary>
					<ErrorBoundary label="Notes panel">
						<NotesPanel />
					</ErrorBoundary>
					<ErrorBoundary label="History timeline">
						<HistoryTimeline />
					</ErrorBoundary>
					<ErrorBoundary label="Attribution panel">
						<OrderAttributionPanel />
					</ErrorBoundary>
					<LegacyMount
						templateId="wc-react-order-edit-tmpl-side"
						className="wc-react-order-edit__legacy-side"
					/>
				</aside>
			</div>
		</div>
	);
}
