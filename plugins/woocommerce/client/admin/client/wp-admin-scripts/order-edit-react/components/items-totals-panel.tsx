/**
 * Items & totals panel — combined: line items table + totals + payment method.
 *
 * Money-affecting actions [Mark as paid] and [Refund] live in this panel
 * (decoupled from status change). Both buttons fire the email-firing-action
 * modal pattern (stub Confirm in v1).
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Modal,
	Notice,
	CheckboxControl,
	TextControl,
	SelectControl,
	Flex,
	FlexItem,
	Card,
	CardHeader,
	CardBody,
} from '@wordpress/components';
import { useOrder } from '../data/order-context';
import { updateOrder, describeError } from '../data/api';
import { EmailIndicator } from './email-indicator';

type ActionType = 'mark-paid' | 'refund' | 'fulfill' | null;

// Row type for the Items table. Carries line items + synthetic summary rows
// (shipping / tax / total). `_kind` lets row styles diverge per row.
type ItemRowKind = 'product' | 'shipping' | 'tax' | 'total';
interface ItemRow {
	id: string;
	_kind: ItemRowKind;
	name: string;
	quantity: number | null;
	price: string;
	total: string;
}

export function ItemsTotalsPanel() {
	const { order } = useOrder();
	const [ activeAction, setActiveAction ] = useState< ActionType >( null );

	const rows: ItemRow[] = useMemo( () => {
		if ( ! order ) {
			return [];
		}
		const symbol = order.currency_symbol || '';
		const products: ItemRow[] = order.line_items.map( ( item ) => ( {
			id: `item-${ item.id }`,
			_kind: 'product',
			name: item.name,
			quantity: item.quantity,
			price: formatCurrency( item.subtotal, symbol ),
			total: formatCurrency( item.total, symbol ),
		} ) );
		const summary: ItemRow[] = [
			{
				id: 'summary-shipping',
				_kind: 'shipping',
				name: __( 'Shipping', 'woocommerce' ),
				quantity: null,
				price: '',
				total: formatCurrency( order.shipping_total || '0', symbol ),
			},
			{
				id: 'summary-tax',
				_kind: 'tax',
				name: __( 'Tax', 'woocommerce' ),
				quantity: null,
				price: '',
				total: formatCurrency( order.total_tax || '0', symbol ),
			},
			{
				id: 'summary-total',
				_kind: 'total',
				name: __( 'Total', 'woocommerce' ),
				quantity: null,
				price: '',
				total: formatCurrency( order.total, symbol ),
			},
		];
		return [ ...products, ...summary ];
	}, [ order ] );

	if ( ! order ) {
		return null;
	}

	// Derive the action-button state from the order's payment + status.
	//   unpaid          → Mark as paid only
	//   paid, processing → Refund + Fulfill order
	//   paid, completed  → Refund only
	//   any other status (cancelled, refunded, failed) → no actions
	const isUnpaid = ! order.date_paid;
	const isProcessing = !! order.date_paid && order.status === 'processing';
	const isCompleted = !! order.date_paid && order.status === 'completed';
	const showActions = isUnpaid || isProcessing || isCompleted;

	return (
		<Card
			className="wc-react-order-edit__panel wc-react-order-edit__items-totals-panel"
			aria-labelledby="wc-react-order-edit-items-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-items-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'Items & totals', 'woocommerce' ) }
				</h2>
				<Button
					variant="tertiary"
					size="compact"
					disabled
					aria-label={ __( 'Editing line items is a Future spec item', 'woocommerce' ) }
				>
					{ __( 'Edit', 'woocommerce' ) }
				</Button>
			</CardHeader>

			<CardBody className="wc-react-order-edit__panel-body">
				<table className="wc-react-order-edit__items-table">
					<thead>
						<tr>
							<th>{ __( 'Product', 'woocommerce' ) }</th>
							<th className="wc-react-order-edit__cell-num">
								{ __( 'Qty', 'woocommerce' ) }
							</th>
							<th className="wc-react-order-edit__cell-num">
								{ __( 'Price', 'woocommerce' ) }
							</th>
							<th className="wc-react-order-edit__cell-num">
								{ __( 'Total', 'woocommerce' ) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ rows.map( ( row ) => (
							<tr
								key={ row.id }
								className={ `wc-react-order-edit__items-row wc-react-order-edit__items-row--${ row._kind }` }
							>
								<td>{ row.name }</td>
								<td className="wc-react-order-edit__cell-num">
									{ row.quantity ?? '' }
								</td>
								<td className="wc-react-order-edit__cell-num">
									{ row.price }
								</td>
								<td className="wc-react-order-edit__cell-num">
									{ row.total }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>

				<p className="wc-react-order-edit__payment-line">
					<strong>{ __( 'Payment method: ', 'woocommerce' ) }</strong>
					{ order.payment_method_title || order.payment_method || __( 'Not set', 'woocommerce' ) }
					{ order.date_paid && (
						<>
							{ ' · ' }
							<em>{ __( 'Paid', 'woocommerce' ) }</em>
						</>
					) }
				</p>
			</CardBody>

			{ showActions && (
				<>
					<hr className="wc-react-order-edit__card-divider" />

					<CardBody className="wc-react-order-edit__panel-body">
						<div className="wc-react-order-edit__money-actions">
							{ isUnpaid && (
								<Button
									variant="primary"
									size="compact"
									onClick={ () => setActiveAction( 'mark-paid' ) }
								>
									{ __( 'Mark as paid', 'woocommerce' ) }
								</Button>
							) }
							{ isProcessing && (
								<>
									<Button
										variant="secondary"
										size="compact"
										onClick={ () => setActiveAction( 'refund' ) }
									>
										{ __( 'Refund', 'woocommerce' ) }
									</Button>
									<Button
										variant="primary"
										size="compact"
										onClick={ () => setActiveAction( 'fulfill' ) }
									>
										{ __( 'Fulfill order', 'woocommerce' ) }
									</Button>
								</>
							) }
							{ isCompleted && (
								<Button
									variant="secondary"
									size="compact"
									onClick={ () => setActiveAction( 'refund' ) }
								>
									{ __( 'Refund', 'woocommerce' ) }
								</Button>
							) }
						</div>
					</CardBody>
				</>
			) }

			{ activeAction === 'mark-paid' && (
				<MarkAsPaidModal
					currencySymbol={ order.currency_symbol }
					onClose={ () => setActiveAction( null ) }
				/>
			) }
			{ activeAction === 'fulfill' && (
				<FulfillOrderModal onClose={ () => setActiveAction( null ) } />
			) }
			{ activeAction === 'refund' && (
				<RefundModal
					orderTotal={ order.total }
					currencySymbol={ order.currency_symbol }
					onClose={ () => setActiveAction( null ) }
				/>
			) }
		</Card>
	);
}

function MarkAsPaidModal( {
	currencySymbol,
	onClose,
}: {
	currencySymbol?: string;
	onClose: () => void;
} ) {
	const { order, setOrder } = useOrder();
	const today = new Date().toISOString().slice( 0, 10 );
	const [ date, setDate ] = useState( today );
	const [ method, setMethod ] = useState( 'bacs' );
	const [ ref, setRef ] = useState( '' );
	const [ suppress, setSuppress ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const handleConfirm = async () => {
		if ( ! order ) {
			return;
		}
		setSaving( true );
		setError( null );
		try {
			// `set_paid: true` triggers WC_Order::payment_complete() server-side
			// (sets date_paid, transitions status, fires payment_complete hooks).
			const updated = await updateOrder( order.id, {
				set_paid: true,
				transaction_id: ref || undefined,
				payment_method: method,
			} );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: { message: __( 'Order marked as paid', 'woocommerce' ) },
				} )
			);
			onClose();
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Modal
			title={ __( 'Mark as paid', 'woocommerce' ) }
			onRequestClose={ saving ? () => undefined : onClose }
			className="wc-react-order-edit__mark-paid-modal"
			shouldCloseOnClickOutside={ ! saving }
			shouldCloseOnEsc={ ! saving }
		>
			<div className="wc-react-order-edit__modal-form">
			<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Date paid', 'woocommerce' ) }
						type="date"
						value={ date }
						onChange={ setDate }
						help={ __(
							'Visual only in v1 — server sets date_paid to now.',
							'woocommerce'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<SelectControl
						label={ __( 'Payment method', 'woocommerce' ) }
						value={ method }
						onChange={ setMethod }
						options={ [
							{ label: __( 'Bank transfer (BACS)', 'woocommerce' ), value: 'bacs' },
							{ label: __( 'Cash on delivery', 'woocommerce' ), value: 'cod' },
							{ label: __( 'Check', 'woocommerce' ), value: 'cheque' },
							{ label: __( 'Other', 'woocommerce' ), value: 'other' },
						] }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<TextControl
				label={ __( 'Reference / transaction ID', 'woocommerce' ) }
				value={ ref }
				onChange={ setRef }
				placeholder={ __( 'Optional', 'woocommerce' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<Notice status="info" isDismissible={ false }>
				<EmailIndicator />
			</Notice>
			<CheckboxControl
				label={ __( "Don't send email for this action", 'woocommerce' ) }
				checked={ suppress }
				onChange={ setSuppress }
				help={ __(
					'Decorative in v1 — v3 REST has no server-side suppress flag yet.',
					'woocommerce'
				) }
				__nextHasNoMarginBottom
			/>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<div className="wc-react-order-edit__modal-actions">
				<Button
					variant="tertiary"
					size="compact"
					onClick={ onClose }
					disabled={ saving }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					size="compact"
					onClick={ handleConfirm }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving…', 'woocommerce' )
						: __( 'Confirm', 'woocommerce' ) }
				</Button>
			</div>
			</div>
		</Modal>
	);
}

function FulfillOrderModal( { onClose }: { onClose: () => void } ) {
	const { order, setOrder } = useOrder();
	const [ suppress, setSuppress ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const handleConfirm = async () => {
		if ( ! order ) {
			return;
		}
		setSaving( true );
		setError( null );
		try {
			const updated = await updateOrder( order.id, { status: 'completed' } );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: { message: __( 'Order fulfilled', 'woocommerce' ) },
				} )
			);
			onClose();
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Modal
			title={ __( 'Fulfill order', 'woocommerce' ) }
			onRequestClose={ saving ? () => undefined : onClose }
			className="wc-react-order-edit__fulfill-modal"
			shouldCloseOnClickOutside={ ! saving }
			shouldCloseOnEsc={ ! saving }
		>
			<div className="wc-react-order-edit__modal-form">
			<p>
				{ __(
					'Marks the order as completed. The customer will receive the "Order completed" email.',
					'woocommerce'
				) }
			</p>

			<Notice status="info" isDismissible={ false }>
				<EmailIndicator />
			</Notice>
			<CheckboxControl
				label={ __( "Don't send email for this action", 'woocommerce' ) }
				checked={ suppress }
				onChange={ setSuppress }
				help={ __(
					'Decorative in v1 — v3 REST has no server-side suppress flag yet.',
					'woocommerce'
				) }
				__nextHasNoMarginBottom
			/>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<div className="wc-react-order-edit__modal-actions">
				<Button
					variant="tertiary"
					size="compact"
					onClick={ onClose }
					disabled={ saving }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					size="compact"
					onClick={ handleConfirm }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving…', 'woocommerce' )
						: __( 'Confirm', 'woocommerce' ) }
				</Button>
			</div>
			</div>
		</Modal>
	);
}

function RefundModal( {
	orderTotal,
	currencySymbol,
	onClose,
}: {
	orderTotal: string;
	currencySymbol?: string;
	onClose: () => void;
} ) {
	const [ amount, setAmount ] = useState( orderTotal );
	const [ reason, setReason ] = useState( '' );
	const [ suppress, setSuppress ] = useState( false );

	return (
		<Modal
			title={ __( 'Refund', 'woocommerce' ) }
			onRequestClose={ onClose }
			className="wc-react-order-edit__refund-modal"
		>
			<div className="wc-react-order-edit__modal-form">
			<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Amount', 'woocommerce' ) }
						value={ amount }
						onChange={ setAmount }
						help={ __( 'Per-item refund / method selector is a Future spec item.', 'woocommerce' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<TextControl
				label={ __( 'Reason (optional)', 'woocommerce' ) }
				value={ reason }
				onChange={ setReason }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<Notice status="info" isDismissible={ false }>
				<EmailIndicator />
			</Notice>
			<CheckboxControl
				label={ __( "Don't send email for this action", 'woocommerce' ) }
				checked={ suppress }
				onChange={ setSuppress }
				__nextHasNoMarginBottom
			/>

			<Notice status="warning" isDismissible={ false }>
				{ __( 'v1 demo: Confirm is a stub. Detailed refund UX is Future spec.', 'woocommerce' ) }
			</Notice>

			<div className="wc-react-order-edit__modal-actions">
				<Button variant="tertiary" size="compact" onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button variant="primary" size="compact" disabled onClick={ onClose }>
					{ __( 'Confirm refund', 'woocommerce' ) }
				</Button>
			</div>
			</div>
		</Modal>
	);
}

function formatCurrency( value: string, symbol = '' ): string {
	if ( ! value ) {
		return symbol + '0';
	}
	return `${ symbol }${ value }`;
}
