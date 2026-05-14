/**
 * Customer panel (merged with Customer History).
 *
 * Identity row (name link + Guest/Registered pill) → EMAIL section →
 * SHIPPING INFORMATION → BILLING INFORMATION → stats footer → OTHER ORDERS
 * (DataView table of the customer's most recent orders, when present).
 *
 * Edit affordance lives only in the CardHeader and opens a single side-panel
 * drawer (CustomerEditPanel) with all editable fields (email + shipping +
 * billing) combined in one form. The per-section modals were retired in
 * favour of this pattern.
 */

import { useState, useEffect, useMemo } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody } from '@wordpress/components';
import { DataViews, type Field, type View } from '@wordpress/dataviews';
import { useOrder } from '../data/order-context';
import { fetchCustomer, fetchCustomerOrders } from '../data/api';
import type { Order, OrderAddress, CustomerSummary } from '../data/types';
import { CustomerEditPanel } from './customer-edit-panel';

export function CustomerPanel() {
	const { order } = useOrder();
	const [ editing, setEditing ] = useState( false );
	const [ customer, setCustomer ] = useState< CustomerSummary | null >( null );

	const customerId = order?.customer_id ?? 0;

	useEffect( () => {
		if ( ! customerId ) {
			setCustomer( null );
			return;
		}
		let cancelled = false;
		fetchCustomer( customerId )
			.then( ( c ) => {
				if ( ! cancelled ) {
					setCustomer( c );
				}
			} )
			.catch( () => {
				// Stats are non-critical — silently ignore fetch failures so the
				// panel still renders the address sections.
			} );
		return () => {
			cancelled = true;
		};
	}, [ customerId ] );

	if ( ! order ) {
		return null;
	}

	const isGuest = customerId === 0;
	const displayName =
		[ order.billing.first_name, order.billing.last_name ]
			.filter( Boolean )
			.join( ' ' ) || __( 'No name', 'woocommerce' );

	return (
		<Card
			className="wc-react-order-edit__panel wc-react-order-edit__customer-panel"
			aria-labelledby="wc-react-order-edit-customer-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-customer-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'Customer', 'woocommerce' ) }
				</h2>
				<Button
					variant="link"
					onClick={ () => setEditing( true ) }
					className="wc-react-order-edit__panel-edit"
				>
					{ __( 'Edit', 'woocommerce' ) }
				</Button>
			</CardHeader>

			<CardBody className="wc-react-order-edit__customer-identity-body">
				<div className="wc-react-order-edit__customer-identity">
					{ customerId > 0 ? (
						<a
							href={ `/wp-admin/user-edit.php?user_id=${ customerId }` }
							className="wc-react-order-edit__customer-name-link"
						>
							{ displayName }
						</a>
					) : (
						<span className="wc-react-order-edit__customer-name-link">
							{ displayName }
						</span>
					) }
					<span className="wc-react-order-edit__customer-pill">
						{ isGuest
							? __( 'Guest', 'woocommerce' )
							: __( 'Registered', 'woocommerce' ) }
					</span>
				</div>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__customer-section">
				<SectionRow label={ __( 'Email', 'woocommerce' ) }>
					{ order.billing.email ? (
						<a href={ `mailto:${ order.billing.email }` }>
							{ order.billing.email }
						</a>
					) : (
						<span className="wc-react-order-edit__empty">
							{ __( 'No email on file.', 'woocommerce' ) }
						</span>
					) }
				</SectionRow>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__customer-section">
				<SectionRow label={ __( 'Shipping information', 'woocommerce' ) }>
					<AddressBlock address={ order.shipping } />
				</SectionRow>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__customer-section">
				<SectionRow label={ __( 'Billing information', 'woocommerce' ) }>
					<AddressBlock address={ order.billing } />
				</SectionRow>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__customer-stats">
				<StatsRow
					customer={ customer }
					isGuest={ isGuest }
					currencySymbol={ order.currency_symbol }
				/>
			</CardBody>

			{ ! isGuest && customerId > 0 && (
				<OtherOrdersSection
					customerId={ customerId }
					excludeOrderId={ order.id }
					currencySymbol={ order.currency_symbol }
				/>
			) }

			{ editing && (
				<CustomerEditPanel onClose={ () => setEditing( false ) } />
			) }
		</Card>
	);
}

interface SectionRowProps {
	label: string;
	children: React.ReactNode;
}

function SectionRow( { label, children }: SectionRowProps ) {
	return (
		<div className="wc-react-order-edit__customer-row">
			<div className="wc-react-order-edit__customer-row-content">
				<h3 className="wc-react-order-edit__subheading">{ label }</h3>
				<div className="wc-react-order-edit__customer-row-value">
					{ children }
				</div>
			</div>
		</div>
	);
}

function AddressBlock( { address }: { address: OrderAddress } ) {
	const lines = [
		[ address.first_name, address.last_name ]
			.filter( Boolean )
			.join( ' ' ),
		address.company,
		address.address_1,
		address.address_2,
		[ address.city, address.state, address.postcode ]
			.filter( Boolean )
			.join( ', ' ),
		address.country,
		address.phone,
	].filter( ( line ): line is string => Boolean( line && line.trim() ) );

	if ( lines.length === 0 ) {
		return (
			<span className="wc-react-order-edit__empty">
				{ __( 'No address on file.', 'woocommerce' ) }
			</span>
		);
	}

	return (
		<address className="wc-react-order-edit__address">
			{ lines.map( ( line, i ) => (
				<div key={ i }>{ line }</div>
			) ) }
		</address>
	);
}

function StatsRow( {
	customer,
	isGuest,
	currencySymbol,
}: {
	customer: CustomerSummary | null;
	isGuest: boolean;
	currencySymbol?: string;
} ) {
	const total = parseFloat( customer?.total_spent || '0' );
	const count = customer?.orders_count || 0;
	const avg = count > 0 ? total / count : 0;
	const symbol = currencySymbol || '';

	if ( isGuest ) {
		return (
			<p className="wc-react-order-edit__empty">
				{ __( 'Guest checkout — no account history available.', 'woocommerce' ) }
			</p>
		);
	}

	return (
		<dl className="wc-react-order-edit__stats">
			<div className="wc-react-order-edit__stat">
				<dt>{ __( 'Total orders', 'woocommerce' ) }</dt>
				<dd>{ count }</dd>
			</div>
			<div className="wc-react-order-edit__stat">
				<dt>{ __( 'Total revenue', 'woocommerce' ) }</dt>
				<dd>
					{ symbol }
					{ total.toFixed( 2 ) }
				</dd>
			</div>
			<div className="wc-react-order-edit__stat">
				<dt>{ __( 'Average order value', 'woocommerce' ) }</dt>
				<dd>
					{ symbol }
					{ avg.toFixed( 2 ) }
				</dd>
			</div>
		</dl>
	);
}

interface OtherOrdersSectionProps {
	customerId: number;
	excludeOrderId: number;
	currencySymbol?: string;
}

/** Flat row type for the other-orders DataView. */
interface OtherOrderRow {
	id: string;
	rawId: number;
	number: string;
	statusSlug: string;
	statusLabel: string;
	dateLabel: string;
	totalLabel: string;
}

/**
 * "Other orders" section rendered at the bottom of the Customer card. Lists
 * the customer's most recent orders (excluding the one currently being
 * edited) in a DataView table, followed by a link to the full Orders list
 * filtered by this customer. Renders nothing if the customer has no other
 * orders.
 */
function OtherOrdersSection( {
	customerId,
	excludeOrderId,
	currencySymbol,
}: OtherOrdersSectionProps ) {
	const [ orders, setOrders ] = useState< Order[] >( [] );
	const [ total, setTotal ] = useState( 0 );

	useEffect( () => {
		let cancelled = false;
		fetchCustomerOrders( customerId, excludeOrderId, 3 )
			.then( ( res ) => {
				if ( cancelled ) {
					return;
				}
				setOrders( res.orders );
				setTotal( res.total );
			} )
			.catch( () => {
				// Non-critical — silently hide the section on fetch errors.
			} );
		return () => {
			cancelled = true;
		};
	}, [ customerId, excludeOrderId ] );

	const symbol = currencySymbol || '';

	const rows: OtherOrderRow[] = useMemo(
		() =>
			orders.map( ( o ) => ( {
				id: String( o.id ),
				rawId: o.id,
				number: `#${ o.number || o.id }`,
				statusSlug: o.status,
				statusLabel: humanizeStatus( o.status ),
				dateLabel: formatOrderDate( o.date_created ),
				totalLabel: `${ symbol }${ parseFloat(
					o.total || '0'
				).toFixed( 2 ) }`,
			} ) ),
		[ orders, symbol ]
	);

	const fields: Field< OtherOrderRow >[] = useMemo(
		() => [
			{
				id: 'number',
				label: __( 'Order number', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<a
						href={ `/wp-admin/admin.php?page=wc-orders&action=edit&id=${ item.rawId }` }
						className="wc-react-order-edit__text-link"
					>
						{ item.number }
					</a>
				),
			},
			{
				id: 'date',
				label: __( 'Date', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<span className="wc-react-order-edit__dv-date">
						{ item.dateLabel }
					</span>
				),
			},
			{
				id: 'status',
				label: __( 'Status', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<span
						className={ `wc-react-order-edit__status-pill wc-react-order-edit__status-pill--${ item.statusSlug }` }
					>
						{ item.statusLabel }
					</span>
				),
			},
			{
				id: 'total',
				label: __( 'Total', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<span className="wc-react-order-edit__dv-num">
						{ item.totalLabel }
					</span>
				),
			},
		],
		[]
	);

	const [ view, setView ] = useState< View >( () => ( {
		type: 'table',
		fields: [ 'date', 'status', 'total' ],
		titleField: 'number',
		page: 1,
		perPage: 100,
	} ) );

	if ( orders.length === 0 ) {
		return null;
	}

	return (
		<>
			<hr className="wc-react-order-edit__card-divider" />
			<CardBody className="wc-react-order-edit__customer-other-orders-section">
				<h3 className="wc-react-order-edit__subheading">
					{ sprintf(
						/* translators: %d: number of other orders */
						_n(
							'Other orders (%d)',
							'Other orders (%d)',
							total,
							'woocommerce'
						),
						total
					) }
				</h3>
				<div className="wc-react-order-edit__dv-shell wc-react-order-edit__other-orders-table">
					<DataViews< OtherOrderRow >
						data={ rows }
						fields={ fields }
						view={ view }
						onChangeView={ setView }
						getItemId={ ( item ) => item.id }
						paginationInfo={ {
							totalItems: rows.length,
							totalPages: 1,
						} }
						search={ false }
						defaultLayouts={ { table: {} } }
						actions={ [] }
					/>
				</div>
				<a
					href={ `/wp-admin/admin.php?page=wc-orders&_customer_user=${ customerId }` }
					className="wc-react-order-edit__text-link wc-react-order-edit__customer-other-orders-link"
				>
					{ __( 'View other orders →', 'woocommerce' ) }
				</a>
			</CardBody>
		</>
	);
}

/** Render a status slug as a human-readable label ("on-hold" → "On hold"). */
function humanizeStatus( slug: string ): string {
	if ( ! slug ) {
		return '';
	}
	return slug
		.replace( /-/g, ' ' )
		.replace( /^\w/, ( c ) => c.toUpperCase() );
}

/** Render an order date in a compact "May 13, 2026" style. The customer
 * card is a narrow column, so we drop the time of day to keep the cell
 * from overflowing into the Status column. Hover/title could surface the
 * full timestamp later if needed. */
function formatOrderDate( iso?: string ): string {
	if ( ! iso ) {
		return '—';
	}
	const date = new Date( iso );
	if ( Number.isNaN( date.getTime() ) ) {
		return '—';
	}
	return date.toLocaleDateString( undefined, {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	} );
}
