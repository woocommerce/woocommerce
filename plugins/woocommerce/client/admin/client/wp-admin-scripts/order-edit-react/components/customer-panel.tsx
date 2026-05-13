/**
 * Customer panel (merged with Customer History).
 *
 * Identity row (avatar + name link + Guest/Registered pill) → EMAIL section →
 * SHIPPING INFORMATION → BILLING INFORMATION → stats footer (total orders,
 * total revenue, average order value). Sections separated by `<CardDivider>`.
 *
 * v1 demo: the Edit per-section buttons all open the same Customer edit modal
 * which currently edits billing address fields. Per-section edit scoping
 * (email-only modal, shipping-only modal) is Future spec.
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardHeader, CardBody } from '@wordpress/components';
import { useOrder } from '../data/order-context';
import { fetchCustomer } from '../data/api';
import type { OrderAddress, CustomerSummary } from '../data/types';
import { EmailEditModal } from './email-edit-modal';
import { AddressEditModal } from './address-edit-modal';

type EditingSection = 'email' | 'shipping' | 'billing' | null;

export function CustomerPanel() {
	const { order } = useOrder();
	const [ editing, setEditing ] = useState< EditingSection >( null );
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
			</CardHeader>

			<CardBody className="wc-react-order-edit__customer-identity-body">
				<div className="wc-react-order-edit__customer-identity">
					<Avatar
						customer={ customer }
						email={ order.billing.email }
						name={ displayName }
					/>
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
				<SectionRow
					label={ __( 'Email', 'woocommerce' ) }
					onEdit={ () => setEditing( 'email' ) }
				>
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
				<SectionRow
					label={ __( 'Shipping information', 'woocommerce' ) }
					onEdit={ () => setEditing( 'shipping' ) }
				>
					<AddressBlock address={ order.shipping } />
				</SectionRow>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__customer-section">
				<SectionRow
					label={ __( 'Billing information', 'woocommerce' ) }
					onEdit={ () => setEditing( 'billing' ) }
				>
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

			{ editing === 'email' && (
				<EmailEditModal onClose={ () => setEditing( null ) } />
			) }
			{ editing === 'shipping' && (
				<AddressEditModal type="shipping" onClose={ () => setEditing( null ) } />
			) }
			{ editing === 'billing' && (
				<AddressEditModal type="billing" onClose={ () => setEditing( null ) } />
			) }
		</Card>
	);
}

function Avatar( {
	customer,
	email,
	name,
}: {
	customer: CustomerSummary | null;
	email?: string;
	name: string;
} ) {
	const src = customer?.avatar_url;
	const initials = name
		.split( /\s+/ )
		.map( ( w ) => w[ 0 ] )
		.filter( Boolean )
		.slice( 0, 2 )
		.join( '' )
		.toUpperCase();

	if ( src ) {
		return (
			<img
				src={ src }
				alt=""
				className="wc-react-order-edit__customer-avatar"
				width={ 32 }
				height={ 32 }
			/>
		);
	}
	return (
		<span
			className="wc-react-order-edit__customer-avatar wc-react-order-edit__customer-avatar--placeholder"
			aria-hidden="true"
		>
			{ initials || '?' }
		</span>
	);
}

interface SectionRowProps {
	label: string;
	onEdit: () => void;
	children: React.ReactNode;
}

function SectionRow( { label, onEdit, children }: SectionRowProps ) {
	return (
		<div className="wc-react-order-edit__customer-row">
			<div className="wc-react-order-edit__customer-row-content">
				<h3 className="wc-react-order-edit__subheading">{ label }</h3>
				<div className="wc-react-order-edit__customer-row-value">
					{ children }
				</div>
			</div>
			<Button variant="link" onClick={ onEdit }>
				{ __( 'Edit', 'woocommerce' ) }
			</Button>
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
