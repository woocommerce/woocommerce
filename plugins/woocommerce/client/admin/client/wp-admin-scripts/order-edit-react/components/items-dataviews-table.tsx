/**
 * Items table built on `@wordpress/dataviews` (table layout).
 *
 * Replaces the hand-rolled `<table>` in items-totals-panel.tsx for the
 * read-only line-items view. Renders four columns:
 *
 *   PRODUCTS  | COST | QTY | TOTAL
 *
 * The Products cell packs in a product thumbnail, the product name, an
 * inline strip of small badges (one per public meta entry — variation
 * attributes + custom options), and the SKU. The right-hand numeric
 * columns are aligned to the right.
 *
 * The DataViews chrome we *don't* want for this static collection
 * (search, filters, pagination, view-switcher, density toggle) is
 * suppressed via props + CSS overrides. We're really just borrowing the
 * design-system-native table layout — none of the queryable affordances
 * apply to a static list of order line items.
 */

import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DataViews, type Field, type View } from '@wordpress/dataviews';
import {
	splitLineItemMeta,
	formatLineItemMeta,
	type OrderLineItem,
} from '../data/types';

interface ItemsDataViewsTableProps {
	lineItems: OrderLineItem[];
	currencySymbol: string;
}

/** Flat row type DataViews works with. `id` is required by getItemId. */
interface LineItemRow {
	id: string;
	rawId: number;
	name: string;
	sku?: string;
	image?: string;
	/** Variation attributes (Size, Color, …) rendered as badges. */
	variantBadges: Array< { key: string; value: string } >;
	/** Other public meta rendered as plain text below the SKU. */
	otherMeta: string[];
	cost: string;
	qty: number;
	total: string;
}

export function ItemsDataViewsTable( {
	lineItems,
	currencySymbol,
}: ItemsDataViewsTableProps ) {
	const rows: LineItemRow[] = useMemo(
		() =>
			lineItems.map( ( item ) => {
				const { variants, other } = splitLineItemMeta( item );
				return {
					id: String( item.id ),
					rawId: item.id,
					name: item.name,
					sku: item.sku,
					image: item.image?.src,
					variantBadges: variants.map( ( { meta } ) => ( {
						key: String( meta.display_key || meta.key ),
						value: String( meta.display_value ?? meta.value ),
					} ) ),
					otherMeta: other.map( ( { meta } ) =>
						formatLineItemMeta( meta )
					),
					cost: formatCurrency( item.subtotal, currencySymbol ),
					qty: item.quantity,
					total: formatCurrency( item.total, currencySymbol ),
				};
			} ),
		[ lineItems, currencySymbol ]
	);

	const fields: Field< LineItemRow >[] = useMemo(
		() => [
			{
				id: 'product',
				label: __( 'Products', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => <ProductCell row={ item } />,
			},
			{
				id: 'cost',
				label: __( 'Cost', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<span className="wc-react-order-edit__dv-num">{ item.cost }</span>
				),
			},
			{
				id: 'qty',
				label: __( 'Qty', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<span className="wc-react-order-edit__dv-num">{ item.qty }</span>
				),
			},
			{
				id: 'total',
				label: __( 'Total', 'woocommerce' ),
				enableSorting: false,
				enableHiding: false,
				render: ( { item } ) => (
					<span className="wc-react-order-edit__dv-num">{ item.total }</span>
				),
			},
		],
		[]
	);

	const [ view, setView ] = useState< View >( () => ( {
		type: 'table',
		fields: [ 'cost', 'qty', 'total' ],
		titleField: 'product',
		// No search, no filters, no per-row actions, no pagination.
		page: 1,
		perPage: 100,
	} ) );

	return (
		<div className="wc-react-order-edit__dv-shell">
			<DataViews< LineItemRow >
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
	);
}

/** Custom Products cell: image · (name / variant badges / SKU / other meta).
 * Each piece is on its own line in the body column. Other meta entries are
 * joined with an em dash so they read as a single inline list. */
function ProductCell( { row }: { row: LineItemRow } ) {
	return (
		<div className="wc-react-order-edit__dv-product">
			<ProductThumb src={ row.image } alt={ row.name } />
			<div className="wc-react-order-edit__dv-product-body">
				<div className="wc-react-order-edit__dv-product-name">
					{ row.name }
				</div>
				{ row.variantBadges.length > 0 && (
					<div className="wc-react-order-edit__dv-badges">
						{ row.variantBadges.map( ( b ) => (
							// Styled span — `Badge` from
							// @wordpress/components crashes with React
							// error #130 in this version (also why the
							// checkout-note label was swapped earlier).
							<span
								key={ b.key }
								className="wc-react-order-edit__dv-badge"
							>
								{ b.value }
							</span>
						) ) }
					</div>
				) }
				{ row.sku && (
					<div className="wc-react-order-edit__dv-sku">{ row.sku }</div>
				) }
				{ row.otherMeta.length > 0 && (
					<div className="wc-react-order-edit__dv-sku">
						{ row.otherMeta.join( ' — ' ) }
					</div>
				) }
			</div>
		</div>
	);
}

function ProductThumb( { src, alt }: { src?: string; alt: string } ) {
	const [ failed, setFailed ] = useState( false );
	if ( ! src || failed ) {
		return (
			<span
				className="wc-react-order-edit__dv-thumb wc-react-order-edit__dv-thumb--placeholder"
				aria-hidden="true"
			/>
		);
	}
	return (
		<img
			className="wc-react-order-edit__dv-thumb"
			src={ src }
			alt={ alt }
			width={ 48 }
			height={ 48 }
			onError={ () => setFailed( true ) }
		/>
	);
}

function formatCurrency( amount: string | number | undefined, symbol: string ) {
	const n = parseFloat( String( amount ?? '0' ) );
	return `${ symbol }${ Number.isFinite( n ) ? n.toFixed( 2 ) : '0.00' }`;
}
