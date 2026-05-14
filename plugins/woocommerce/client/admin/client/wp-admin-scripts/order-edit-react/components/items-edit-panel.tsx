/**
 * Items & totals edit drawer — slide-in side panel for editing line items.
 *
 * Current scope: edit existing line items (qty / subtotal / total, delete),
 * edit line-item meta, and add new products via the picker modal (Phase 2).
 * Fees, shipping, coupons, and tax recalc are later phases. Refund and
 * fulfillment are handled by separate flows.
 *
 * Pattern: matches the CustomerEditPanel — 450px right-pinned drawer with
 * dark backdrop, Esc/X/Cancel/backdrop-click to close, single PUT on Save.
 */

import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Notice,
	TextControl,
	Flex,
	FlexItem,
	__experimentalNumberControl as NumberControl,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import {
	close as closeIcon,
	closeSmall,
	plus as plusIcon,
	trash as trashIcon,
} from '@wordpress/icons';
import { useOrder } from '../data/order-context';
import { updateOrder, describeError } from '../data/api';
import {
	splitLineItemMeta,
	type OrderLineItem,
	type OrderLineItemMeta,
} from '../data/types';
import {
	ProductPickerModal,
	type ProductPickerSelection,
} from './product-picker-modal';

interface ItemsEditPanelProps {
	onClose: () => void;
}

/**
 * A line item that the user may have edited locally. Server-fetched items
 * keep their `id`; freshly-added items (Phase 2 product picker) start
 * without one and carry a `_tempKey` so React keys and our state lookups
 * stay stable until Save assigns a real id.
 */
interface EditableLineItem extends Omit< OrderLineItem, 'id' > {
	id?: number;
	/** Stable client-side key for items that don't have a server `id` yet. */
	_tempKey?: string;
	/**
	 * Flagged when the user clicks the delete affordance. Marked items
	 * still render (greyed out, with Undo), so the user can recover before
	 * saving. New items (no `id`) get dropped from the payload entirely.
	 */
	_deleted?: boolean;
	/**
	 * Transient flag for the add-product highlight pulse. Cleared after
	 * the animation has had time to play.
	 */
	_justAdded?: boolean;
}

/**
 * Stable string key for an editable line item — server id when present,
 * otherwise the client-side temp key assigned at insert.
 */
function itemKey( item: EditableLineItem ): string {
	if ( item.id !== undefined ) {
		return `s-${ item.id }`;
	}
	return item._tempKey ?? '__unkeyed__';
}

export function ItemsEditPanel( { onClose }: ItemsEditPanelProps ) {
	const { order, setOrder } = useOrder();

	// Snapshot the order's line items into local editable state on mount.
	const [ items, setItems ] = useState< EditableLineItem[] >( () =>
		order ? order.line_items.map( ( i ) => ( { ...i } ) ) : []
	);

	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );
	const [ pickerOpen, setPickerOpen ] = useState( false );

	// Counter for unique _tempKey on freshly added items. Date.now() can
	// collide if a user rapid-clicks; pairing with a monotonic counter is
	// trivial insurance.
	const tempKeyCounter = useRef( 0 );

	// Esc closes the drawer (only when not mid-save so we don't lose work).
	useEffect( () => {
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' && ! saving ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onClose, saving ] );

	// Lock background page scroll while the drawer is open so the user
	// only scrolls inside the drawer body. Restored on unmount.
	useEffect( () => {
		document.body.classList.add(
			'wc-react-order-edit__body-scroll-locked'
		);
		return () => {
			document.body.classList.remove(
				'wc-react-order-edit__body-scroll-locked'
			);
		};
	}, [] );

	// After a new item is appended, scroll it into view and clear the
	// transient highlight flag so subsequent edits don't re-animate the row.
	useEffect( () => {
		const newest = items.find( ( i ) => i._justAdded );
		if ( ! newest ) {
			return;
		}
		const key = itemKey( newest );
		const node = document.querySelector(
			`[data-item-key="${ key }"]`
		) as HTMLElement | null;
		if ( node ) {
			node.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}
		const timer = window.setTimeout( () => {
			setItems( ( prev ) =>
				prev.map( ( i ) =>
					i._justAdded ? { ...i, _justAdded: false } : i
				)
			);
		}, 1500 );
		return () => window.clearTimeout( timer );
	}, [ items ] );

	// Live preview of the items subtotal. Server is authoritative on full
	// totals; this is just a rough indicator of what the user is doing.
	const itemsSubtotal = useMemo( () => {
		return items
			.filter( ( i ) => ! i._deleted )
			.reduce( ( sum, i ) => sum + ( parseFloat( i.total ) || 0 ), 0 );
	}, [ items ] );

	if ( ! order ) {
		return null;
	}

	const symbol = order.currency_symbol || '';

	const updateItem = ( key: string, patch: Partial< EditableLineItem > ) => {
		setItems( ( prev ) =>
			prev.map( ( i ) =>
				itemKey( i ) === key ? { ...i, ...patch } : i
			)
		);
	};

	const toggleDelete = ( key: string ) => {
		setItems( ( prev ) =>
			prev.map( ( i ) =>
				itemKey( i ) === key ? { ...i, _deleted: ! i._deleted } : i
			)
		);
	};

	// Meta operations work on indices in the FULL meta_data array (not the
	// filtered "public" view). Hidden meta (keys prefixed with `_`) is left
	// untouched in state and re-sent on Save so we don't accidentally drop
	// internal flags.
	const updateMeta = (
		key: string,
		metaIndex: number,
		patch: Partial< OrderLineItemMeta >
	) => {
		setItems( ( prev ) =>
			prev.map( ( i ) => {
				if ( itemKey( i ) !== key ) {
					return i;
				}
				const next = [ ...( i.meta_data || [] ) ];
				next[ metaIndex ] = { ...next[ metaIndex ], ...patch };
				return { ...i, meta_data: next };
			} )
		);
	};

	const deleteMeta = ( key: string, metaIndex: number ) => {
		setItems( ( prev ) =>
			prev.map( ( i ) => {
				if ( itemKey( i ) !== key ) {
					return i;
				}
				return {
					...i,
					meta_data: ( i.meta_data || [] ).filter(
						( _, idx ) => idx !== metaIndex
					),
				};
			} )
		);
	};

	const handleProductsPicked = (
		newSelections: ProductPickerSelection[]
	) => {
		if ( newSelections.length === 0 ) {
			setPickerOpen( false );
			return;
		}
		const now = Date.now();
		const newItems: EditableLineItem[] = newSelections.map(
			( selection, idx ) => {
				tempKeyCounter.current += 1;
				const price = selection.price || '0';
				return {
					_tempKey: `new-${ now }-${ tempKeyCounter.current }-${ idx }`,
					// Only the first new item carries the highlight; flagging
					// all of them would trigger a scroll/animation race.
					_justAdded: idx === 0,
					name: selection.name,
					product_id: selection.product_id,
					variation_id: selection.variation_id,
					quantity: 1,
					subtotal: price,
					total: price,
					sku: selection.sku,
					image: selection.image,
					meta_data: [],
				};
			}
		);
		setItems( ( prev ) => [ ...prev, ...newItems ] );
		setPickerOpen( false );
	};

	const shipping = parseFloat( order.shipping_total || '0' );
	const tax = parseFloat( order.total_tax || '0' );
	const discount = parseFloat( order.discount_total || '0' );
	const previewTotal = itemsSubtotal + shipping + tax - discount;

	const handleSave = async () => {
		setSaving( true );
		setError( null );
		try {
			// Build the line_items array for the PUT.
			//  - New items (no `id`) flagged deleted are dropped entirely.
			//  - Existing items flagged deleted are sent as { id, quantity: 0 }
			//    which removes them in v3.
			//  - Existing items go as full updates so the server recomputes
			//    taxes/totals from the new values.
			//  - New items (Phase 2 product picker) send just product_id,
			//    optional variation_id, and quantity. We omit subtotal/total
			//    so the server uses the current product price for the line.
			const line_items = items
				.filter(
					( item ) => ! ( item._deleted && item.id === undefined )
				)
				.map( ( item ) => {
					if ( item._deleted ) {
						return { id: item.id, quantity: 0 };
					}
					const metaData = ( item.meta_data || [] )
						.filter(
							( m ) => m.key && String( m.key ).trim() !== ''
						)
						.map( ( m ) => ( {
							...( m.id ? { id: m.id } : {} ),
							key: m.key,
							value: String( m.value ?? '' ),
						} ) );
					if ( item.id === undefined ) {
						return {
							product_id: item.product_id,
							...( item.variation_id
								? { variation_id: item.variation_id }
								: {} ),
							quantity: item.quantity,
							meta_data: metaData,
						};
					}
					return {
						id: item.id,
						quantity: item.quantity,
						subtotal: item.subtotal,
						total: item.total,
						// Pass the whole meta_data array — v3 replaces the
						// line item's meta with what we send. Empty rows (no
						// key) are filtered out so they don't create blank
						// entries on the server.
						meta_data: metaData,
					};
				} );

			const updated = await updateOrder( order.id, {
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				line_items: line_items as any,
			} );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: {
						message: __( 'Items updated', 'woocommerce' ),
					},
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
		<>
			<div
				className={ `wc-react-order-edit__drawer-backdrop${
					pickerOpen
						? ' wc-react-order-edit__drawer-backdrop--behind-modal'
						: ''
				}` }
				onClick={ saving ? undefined : onClose }
				aria-hidden="true"
			/>
			<aside
				className={ `wc-react-order-edit__drawer${
					pickerOpen
						? ' wc-react-order-edit__drawer--behind-modal'
						: ''
				}` }
				role="dialog"
				aria-modal="true"
				aria-labelledby="wc-react-order-edit-items-drawer-title"
			>
				<header className="wc-react-order-edit__drawer-header">
					<h2
						id="wc-react-order-edit-items-drawer-title"
						className="wc-react-order-edit__drawer-title"
					>
						{ __( 'Edit items & totals', 'woocommerce' ) }
					</h2>
					<Button
						icon={ closeIcon }
						label={ __( 'Close', 'woocommerce' ) }
						onClick={ onClose }
						disabled={ saving }
					/>
				</header>

				<div className="wc-react-order-edit__drawer-body">
					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }

					<section className="wc-react-order-edit__drawer-section">
						<div className="wc-react-order-edit__drawer-section-header">
							<Heading
								level={ 5 }
								className="wc-react-order-edit__drawer-section-title"
							>
								{ __( 'Order items', 'woocommerce' ) }
							</Heading>
						</div>

						{ items.length === 0 ? (
							<p className="wc-react-order-edit__empty">
								{ __(
									'No line items yet — use “+ Add product” to add the first one.',
									'woocommerce'
								) }
							</p>
						) : (
							<div className="wc-react-order-edit__items-edit-list">
								{ items.map( ( item ) => {
									const key = itemKey( item );
									return (
										<LineItemCard
											key={ key }
											itemKey={ key }
											item={ item }
											symbol={ symbol }
											onChange={ ( patch ) =>
												updateItem( key, patch )
											}
											onToggleDelete={ () =>
												toggleDelete( key )
											}
											onMetaChange={ ( idx, patch ) =>
												updateMeta( key, idx, patch )
											}
											onMetaDelete={ ( idx ) =>
												deleteMeta( key, idx )
											}
										/>
									);
								} ) }
							</div>
						) }
						<div className="wc-react-order-edit__items-edit-add-row">
							<Button
								variant="secondary"
								icon={ plusIcon }
								onClick={ () => setPickerOpen( true ) }
								disabled={ saving }
							>
								{ __( 'Add product', 'woocommerce' ) }
							</Button>
						</div>
					</section>

					<TotalsSummary
						symbol={ symbol }
						itemsSubtotal={ itemsSubtotal }
						shipping={ shipping }
						discount={ discount }
						tax={ tax }
						total={ previewTotal }
					/>
				</div>

				{ pickerOpen && (
					<ProductPickerModal
						currencySymbol={ symbol }
						onPick={ handleProductsPicked }
						onClose={ () => setPickerOpen( false ) }
					/>
				) }
				<footer className="wc-react-order-edit__drawer-footer">
					<Button
						variant="secondary"
						disabled
						className="wc-react-order-edit__drawer-footer-recalc"
						aria-label={ __(
							'Tax recalculation arrives with the Tax phase.',
							'woocommerce'
						) }
					>
						{ __( 'Recalculate', 'woocommerce' ) }
					</Button>
					<Button
						variant="tertiary"
						onClick={ onClose }
						disabled={ saving }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ saving }
						disabled={ saving }
					>
						{ saving
							? __( 'Saving…', 'woocommerce' )
							: __( 'Save', 'woocommerce' ) }
					</Button>
				</footer>
			</aside>
		</>
	);
}

interface LineItemCardProps {
	item: EditableLineItem;
	itemKey: string;
	symbol: string;
	onChange: ( patch: Partial< EditableLineItem > ) => void;
	onToggleDelete: () => void;
	onMetaChange: (
		metaIndex: number,
		patch: Partial< OrderLineItemMeta >
	) => void;
	onMetaDelete: ( metaIndex: number ) => void;
}

function LineItemCard( {
	item,
	itemKey: cardKey,
	symbol,
	onChange,
	onToggleDelete,
	onMetaChange,
	onMetaDelete,
}: LineItemCardProps ) {
	// Variation attributes are immutable (set on the product config), so they
	// render read-only beneath the row. Other public meta is editable inline.
	// Hidden meta (prefixed `_`) is preserved in state for round-tripping but
	// never shown.
	const { variants, other } = splitLineItemMeta( item );

	const isDeleted = !! item._deleted;

	const classes = [
		'wc-react-order-edit__item-edit-card',
		isDeleted ? 'wc-react-order-edit__item-edit-card--deleted' : '',
		item._justAdded ? 'wc-react-order-edit__item-edit-card--new' : '',
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div className={ classes } data-item-key={ cardKey }>
			<div className="wc-react-order-edit__item-edit-card-header">
				<ProductThumb src={ item.image?.src } alt={ item.name } />
				<div className="wc-react-order-edit__item-edit-card-name">
					<span className="wc-react-order-edit__item-edit-card-name-row">
						<strong>{ item.name }</strong>
						{ variants.length > 0 && (
							<span className="wc-react-order-edit__item-edit-card-badges">
								{ variants.map( ( { meta, index } ) => (
									<span
										key={ meta.id ?? `var-${ index }` }
										className="wc-react-order-edit__dv-badge"
									>
										{ String(
											meta.display_value ?? meta.value
										) }
									</span>
								) ) }
							</span>
						) }
					</span>
					{ item.sku && (
						<span className="wc-react-order-edit__item-edit-card-sku">
							{ __( 'SKU:', 'woocommerce' ) } { item.sku }
						</span>
					) }
				</div>
				<Button
					icon={ trashIcon }
					size="small"
					label={
						isDeleted
							? __( 'Undo remove', 'woocommerce' )
							: __( 'Remove item', 'woocommerce' )
					}
					onClick={ onToggleDelete }
					isPressed={ isDeleted }
				/>
			</div>

			{ isDeleted ? (
				<p className="wc-react-order-edit__item-edit-card-deleted-note">
					{ __( 'Will be removed on Save.', 'woocommerce' ) }
				</p>
			) : (
				<>
					<Flex gap={ 3 } wrap>
						<FlexItem isBlock>
							<NumberControl
								label={ __( 'Qty', 'woocommerce' ) }
								value={ item.quantity }
								onChange={ ( value ) =>
									onChange( {
										quantity: Math.max(
											0,
											Number( value ) || 0
										),
									} )
								}
								min={ 0 }
								__next40pxDefaultSize
							/>
						</FlexItem>
						<FlexItem isBlock>
							<TextControl
								label={ `${ __(
									'Total',
									'woocommerce'
								) } (${ symbol })` }
								value={ item.total }
								onChange={ ( value ) =>
									onChange( { total: value } )
								}
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						</FlexItem>
					</Flex>

					{ other.length > 0 && (
						<OtherMetaSection
							entries={ other }
							onMetaChange={ onMetaChange }
							onMetaDelete={ onMetaDelete }
						/>
					) }
				</>
			) }
		</div>
	);
}

interface OtherMetaSectionProps {
	entries: Array< { meta: OrderLineItemMeta; index: number } >;
	onMetaChange: (
		metaIndex: number,
		patch: Partial< OrderLineItemMeta >
	) => void;
	onMetaDelete: ( metaIndex: number ) => void;
}

/**
 * Shows non-variant line-item meta with an Edit affordance. Read-only by
 * default; an "Edit" link toggles the section into editable key/value rows
 * with delete buttons. Changes are kept in state regardless of section
 * expansion — the drawer-level Save commits; Cancel reverts.
 */
function OtherMetaSection( {
	entries,
	onMetaChange,
	onMetaDelete,
}: OtherMetaSectionProps ) {
	const [ editing, setEditing ] = useState( false );

	return (
		<div className="wc-react-order-edit__item-edit-card-meta-section">
			<div className="wc-react-order-edit__item-edit-card-meta-header">
				<h4 className="wc-react-order-edit__item-edit-card-meta-heading">
					{ __( 'Other details', 'woocommerce' ) }
				</h4>
				<Button
					variant="link"
					size="small"
					onClick={ () => setEditing( ( v ) => ! v ) }
				>
					{ editing
						? __( 'Done', 'woocommerce' )
						: __( 'Edit', 'woocommerce' ) }
				</Button>
			</div>

			{ editing
				? entries.map( ( { meta, index } ) => (
						<MetaRow
							key={ meta.id ?? `new-${ index }` }
							meta={ meta }
							onChange={ ( patch ) =>
								onMetaChange( index, patch )
							}
							onDelete={ () => onMetaDelete( index ) }
						/>
				  ) )
				: entries.map( ( { meta, index } ) => (
						<div
							key={ meta.id ?? `idx-${ index }` }
							className="wc-react-order-edit__item-edit-card-meta-display-row"
						>
							<span className="wc-react-order-edit__item-edit-card-meta-display-key">
								{ String( meta.display_key || meta.key ) }
							</span>
							<span className="wc-react-order-edit__item-edit-card-meta-display-value">
								{ String( meta.display_value ?? meta.value ) }
							</span>
						</div>
				  ) ) }
		</div>
	);
}

interface MetaRowProps {
	meta: OrderLineItemMeta;
	onChange: ( patch: Partial< OrderLineItemMeta > ) => void;
	onDelete: () => void;
}

function MetaRow( { meta, onChange, onDelete }: MetaRowProps ) {
	// Editing operates on the raw `key`/`value` fields. WC recomputes
	// display_key/display_value server-side on save. For meta tied to
	// taxonomy attributes (`pa_*`), renaming the key here may dissociate
	// it from the taxonomy — that's intentional for an experiment seam,
	// but worth flagging if we surface a "this attribute is locked" UX.
	return (
		<div className="wc-react-order-edit__item-edit-card-meta-row">
			<Flex gap={ 2 } align="flex-end">
				<FlexItem>
					<TextControl
						label={ __( 'Key', 'woocommerce' ) }
						hideLabelFromVision
						value={ String( meta.key ?? '' ) }
						onChange={ ( v ) => onChange( { key: v } ) }
						placeholder={ __( 'Key', 'woocommerce' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Value', 'woocommerce' ) }
						hideLabelFromVision
						value={ String( meta.value ?? '' ) }
						onChange={ ( v ) => onChange( { value: v } ) }
						placeholder={ __( 'Value', 'woocommerce' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<Button
					icon={ closeSmall }
					size="small"
					label={ __( 'Remove custom field', 'woocommerce' ) }
					onClick={ onDelete }
				/>
			</Flex>
		</div>
	);
}

/**
 * Small product thumbnail with a graceful fallback when the line item
 * has no image (or the image URL fails to load). The placeholder uses a
 * subtle grey square so the row layout stays consistent across items.
 */
function ProductThumb( { src, alt }: { src?: string; alt: string } ) {
	const [ failed, setFailed ] = useState( false );
	if ( ! src || failed ) {
		return (
			<span
				className="wc-react-order-edit__item-edit-card-thumb wc-react-order-edit__item-edit-card-thumb--placeholder"
				aria-hidden="true"
			/>
		);
	}
	return (
		<img
			className="wc-react-order-edit__item-edit-card-thumb"
			src={ src }
			alt={ alt }
			width={ 36 }
			height={ 36 }
			onError={ () => setFailed( true ) }
		/>
	);
}

interface TotalsSummaryProps {
	symbol: string;
	itemsSubtotal: number;
	shipping: number;
	discount: number;
	tax: number;
	total: number;
}

function TotalsSummary( {
	symbol,
	itemsSubtotal,
	shipping,
	discount,
	tax,
	total,
}: TotalsSummaryProps ) {
	return (
		<section className="wc-react-order-edit__drawer-totals">
			<div className="wc-react-order-edit__drawer-totals-row">
				<span>{ __( 'Items subtotal', 'woocommerce' ) }</span>
				<span>
					{ symbol }
					{ itemsSubtotal.toFixed( 2 ) }
				</span>
			</div>
			<div className="wc-react-order-edit__drawer-totals-row">
				<span>{ __( 'Shipping', 'woocommerce' ) }</span>
				<span>
					{ symbol }
					{ shipping.toFixed( 2 ) }
				</span>
			</div>
			{ discount > 0 && (
				<div className="wc-react-order-edit__drawer-totals-row">
					<span>{ __( 'Discount', 'woocommerce' ) }</span>
					<span>
						−{ symbol }
						{ discount.toFixed( 2 ) }
					</span>
				</div>
			) }
			<div className="wc-react-order-edit__drawer-totals-row">
				<span>{ __( 'Tax', 'woocommerce' ) }</span>
				<span>
					{ symbol }
					{ tax.toFixed( 2 ) }
				</span>
			</div>
			<div className="wc-react-order-edit__drawer-totals-row wc-react-order-edit__drawer-totals-row--grand">
				<span>{ __( 'Total (preview)', 'woocommerce' ) }</span>
				<span>
					{ symbol }
					{ total.toFixed( 2 ) }
				</span>
			</div>
			<p className="wc-react-order-edit__drawer-totals-note">
				{ __(
					'Final totals are recalculated on Save.',
					'woocommerce'
				) }
			</p>
		</section>
	);
}
