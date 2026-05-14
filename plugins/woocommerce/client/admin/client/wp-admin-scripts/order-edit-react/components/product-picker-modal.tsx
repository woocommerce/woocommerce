/**
 * Product picker modal — opens on top of the Items edit drawer.
 *
 * Two views inside one Modal:
 *  - "search": debounced REST search of `/wc/v3/products`. Each result row
 *    shows thumb/name/SKU/price. Simple products carry a checkbox. Variable
 *    parents show a "Choose variation →" affordance (no checkbox — the
 *    variation, not the parent, is what gets added).
 *  - "variations": after picking a variable parent, fetch
 *    `/wc/v3/products/{id}/variations` and show one row per variation, each
 *    with a checkbox. Back button returns to the search results.
 *
 * The user accumulates a multi-product selection across views and commits
 * them all at once with the footer "Add products" button. The selection
 * persists when switching between search and variations views.
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import {
	Modal,
	Button,
	TextControl,
	Spinner,
	Notice,
} from '@wordpress/components';
import { arrowLeft } from '@wordpress/icons';
import {
	searchProducts,
	fetchProductVariations,
	describeError,
} from '../data/api';
import type {
	ProductSearchResult,
	ProductVariationResult,
} from '../data/types';

export interface ProductPickerSelection {
	product_id: number;
	variation_id?: number;
	name: string;
	sku?: string;
	image?: { src?: string };
	/**
	 * Numeric string from the product or variation; used to seed
	 * subtotal/total in the new line item's local state.
	 */
	price: string;
}

interface ProductPickerModalProps {
	currencySymbol: string;
	onPick: ( selections: ProductPickerSelection[] ) => void;
	onClose: () => void;
}

/** Composite selection key: `<product_id>|<variation_id>`. Variation 0
 * means "no variation" (simple product). Stable across search/variations
 * views so a checkbox checked in one view stays checked after navigating. */
function selectionKey( productId: number, variationId?: number ): string {
	return `${ productId }|${ variationId ?? 0 }`;
}

export function ProductPickerModal( {
	currencySymbol,
	onPick,
	onClose,
}: ProductPickerModalProps ) {
	const [ view, setView ] = useState< 'search' | 'variations' >( 'search' );
	const [ query, setQuery ] = useState( '' );
	const [ results, setResults ] = useState< ProductSearchResult[] >( [] );
	const [ searching, setSearching ] = useState( false );
	const [ searchError, setSearchError ] = useState< string | null >( null );

	const [ parent, setParent ] = useState< ProductSearchResult | null >(
		null
	);
	const [ variations, setVariations ] = useState< ProductVariationResult[] >(
		[]
	);
	const [ loadingVariations, setLoadingVariations ] = useState( false );
	const [ variationsError, setVariationsError ] = useState< string | null >(
		null
	);

	// Map of selection key → ProductPickerSelection. Survives view switches.
	const [ selections, setSelections ] = useState<
		Map< string, ProductPickerSelection >
	>( () => new Map() );

	// Debounced product search. Empty query is intentional: on mount the
	// modal shows the first page of published products (alphabetical) so the
	// merchant sees the catalog immediately. Typing narrows it.
	useEffect( () => {
		const trimmed = query.trim();
		let cancelled = false;
		setSearching( true );
		setSearchError( null );
		const timer = window.setTimeout( async () => {
			try {
				const data = await searchProducts( trimmed );
				if ( cancelled ) {
					return;
				}
				setResults( data );
			} catch ( err ) {
				if ( cancelled ) {
					return;
				}
				setSearchError( describeError( err ) );
				setResults( [] );
			} finally {
				if ( ! cancelled ) {
					setSearching( false );
				}
			}
		}, 300 );
		return () => {
			cancelled = true;
			window.clearTimeout( timer );
		};
	}, [ query ] );

	const handleParentClick = async ( product: ProductSearchResult ) => {
		setParent( product );
		setView( 'variations' );
		setVariations( [] );
		setLoadingVariations( true );
		setVariationsError( null );
		try {
			const data = await fetchProductVariations( product.id );
			setVariations( data );
		} catch ( err ) {
			setVariationsError( describeError( err ) );
		} finally {
			setLoadingVariations( false );
		}
	};

	const handleBackToSearch = () => {
		setView( 'search' );
		setParent( null );
		setVariations( [] );
		setVariationsError( null );
	};

	const toggleSelection = (
		key: string,
		selection: ProductPickerSelection
	) => {
		setSelections( ( prev ) => {
			const next = new Map( prev );
			if ( next.has( key ) ) {
				next.delete( key );
			} else {
				next.set( key, selection );
			}
			return next;
		} );
	};

	const toggleSimpleProduct = ( product: ProductSearchResult ) => {
		const key = selectionKey( product.id );
		toggleSelection( key, {
			product_id: product.id,
			name: product.name,
			sku: product.sku,
			image: { src: product.images?.[ 0 ]?.src },
			price: priceFor( product ),
		} );
	};

	const toggleVariation = ( variation: ProductVariationResult ) => {
		if ( ! parent ) {
			return;
		}
		const key = selectionKey( parent.id, variation.id );
		toggleSelection( key, {
			product_id: parent.id,
			variation_id: variation.id,
			name: variationDisplayName( parent, variation ),
			sku: variation.sku || parent.sku,
			image: { src: variation.image?.src || parent.images?.[ 0 ]?.src },
			price: priceFor( variation ),
		} );
	};

	const handleAddProducts = () => {
		if ( selections.size === 0 ) {
			return;
		}
		onPick( Array.from( selections.values() ) );
	};

	const title = useMemo( () => {
		if ( view === 'variations' && parent ) {
			return sprintf(
				/* translators: %s: parent product name */
				__( 'Choose variations of %s', 'woocommerce' ),
				parent.name
			);
		}
		return __( 'Add products', 'woocommerce' );
	}, [ view, parent ] );

	const count = selections.size;
	const addLabel =
		count === 0
			? __( 'Add products', 'woocommerce' )
			: sprintf(
					/* translators: %d: number of products selected */
					_n(
						'Add %d product',
						'Add %d products',
						count,
						'woocommerce'
					),
					count
			  );

	return (
		<Modal
			title={ title }
			onRequestClose={ onClose }
			className="wc-react-order-edit__product-picker-modal"
		>
			<div className="wc-react-order-edit__product-picker">
				{ view === 'search' ? (
					<>
						<TextControl
							label={ __( 'Search products', 'woocommerce' ) }
							hideLabelFromVision
							value={ query }
							onChange={ setQuery }
							placeholder={ __(
								'Search by product name or SKU',
								'woocommerce'
							) }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<SearchBody
							query={ query }
							results={ results }
							searching={ searching }
							error={ searchError }
							currencySymbol={ currencySymbol }
							selections={ selections }
							onToggleSimple={ toggleSimpleProduct }
							onDrillIn={ handleParentClick }
						/>
					</>
				) : (
					<>
						<Button
							variant="tertiary"
							icon={ arrowLeft }
							onClick={ handleBackToSearch }
							className="wc-react-order-edit__product-picker-back"
						>
							{ __( 'Back to search', 'woocommerce' ) }
						</Button>
						<VariationsBody
							loading={ loadingVariations }
							error={ variationsError }
							variations={ variations }
							parent={ parent }
							currencySymbol={ currencySymbol }
							selections={ selections }
							onToggleVariation={ toggleVariation }
						/>
					</>
				) }
				<footer className="wc-react-order-edit__product-picker-footer">
					<Button variant="tertiary" onClick={ onClose }>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handleAddProducts }
						disabled={ count === 0 }
					>
						{ addLabel }
					</Button>
				</footer>
			</div>
		</Modal>
	);
}

interface SearchBodyProps {
	query: string;
	results: ProductSearchResult[];
	searching: boolean;
	error: string | null;
	currencySymbol: string;
	selections: Map< string, ProductPickerSelection >;
	onToggleSimple: ( product: ProductSearchResult ) => void;
	onDrillIn: ( product: ProductSearchResult ) => void;
}

function SearchBody( {
	query,
	results,
	searching,
	error,
	currencySymbol,
	selections,
	onToggleSimple,
	onDrillIn,
}: SearchBodyProps ) {
	const trimmed = query.trim();

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	if ( searching ) {
		return (
			<div
				className="wc-react-order-edit__product-picker-loading"
				role="status"
				aria-live="polite"
			>
				<Spinner />
				<span>
					{ trimmed === ''
						? __( 'Loading products…', 'woocommerce' )
						: __( 'Searching products…', 'woocommerce' ) }
				</span>
			</div>
		);
	}

	if ( results.length === 0 ) {
		return (
			<p className="wc-react-order-edit__product-picker-empty">
				{ trimmed === ''
					? __( 'No published products found.', 'woocommerce' )
					: __( 'No products matched that search.', 'woocommerce' ) }
			</p>
		);
	}

	return (
		<ul
			className="wc-react-order-edit__product-picker-results"
			aria-label={ __( 'Product results', 'woocommerce' ) }
		>
			{ results.map( ( product ) => (
				<ProductRow
					key={ product.id }
					product={ product }
					currencySymbol={ currencySymbol }
					isSelected={ selections.has( selectionKey( product.id ) ) }
					onToggleSimple={ onToggleSimple }
					onDrillIn={ onDrillIn }
				/>
			) ) }
		</ul>
	);
}

interface ProductRowProps {
	product: ProductSearchResult;
	currencySymbol: string;
	isSelected: boolean;
	onToggleSimple: ( product: ProductSearchResult ) => void;
	onDrillIn: ( product: ProductSearchResult ) => void;
}

function ProductRow( {
	product,
	currencySymbol,
	isSelected,
	onToggleSimple,
	onDrillIn,
}: ProductRowProps ) {
	const isVariable = product.type === 'variable';
	const outOfStock = product.stock_status === 'outofstock';
	const thumb = product.images?.[ 0 ]?.src;
	const rowClass = `wc-react-order-edit__product-picker-row-button${
		outOfStock
			? ' wc-react-order-edit__product-picker-row-button--oos'
			: ''
	}`;

	if ( isVariable ) {
		// Variable parents drill in to their variations view. No checkbox —
		// the variation is what enters the order, not the parent.
		return (
			<li className="wc-react-order-edit__product-picker-row">
				<button
					type="button"
					className={ rowClass }
					onClick={ () => onDrillIn( product ) }
				>
					<span
						className="wc-react-order-edit__product-picker-row-checkslot"
						aria-hidden="true"
					/>
					<PickerThumb src={ thumb } alt={ product.name } />
					<RowMain
						name={ product.name }
						sku={ product.sku }
						outOfStock={ outOfStock }
					/>
					<RowSide
						currencySymbol={ currencySymbol }
						price={ priceFor( product ) }
						action={ __( 'Choose variation →', 'woocommerce' ) }
					/>
				</button>
			</li>
		);
	}

	return (
		<li className="wc-react-order-edit__product-picker-row">
			<label className={ rowClass }>
				<input
					type="checkbox"
					className="wc-react-order-edit__product-picker-row-checkbox"
					checked={ isSelected }
					onChange={ () => onToggleSimple( product ) }
				/>
				<PickerThumb src={ thumb } alt={ product.name } />
				<RowMain
					name={ product.name }
					sku={ product.sku }
					outOfStock={ outOfStock }
				/>
				<RowSide
					currencySymbol={ currencySymbol }
					price={ priceFor( product ) }
				/>
			</label>
		</li>
	);
}

interface VariationsBodyProps {
	loading: boolean;
	error: string | null;
	variations: ProductVariationResult[];
	parent: ProductSearchResult | null;
	currencySymbol: string;
	selections: Map< string, ProductPickerSelection >;
	onToggleVariation: ( variation: ProductVariationResult ) => void;
}

function VariationsBody( {
	loading,
	error,
	variations,
	parent,
	currencySymbol,
	selections,
	onToggleVariation,
}: VariationsBodyProps ) {
	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}
	if ( loading ) {
		return (
			<div
				className="wc-react-order-edit__product-picker-loading"
				role="status"
				aria-live="polite"
			>
				<Spinner />
				<span>{ __( 'Loading variations…', 'woocommerce' ) }</span>
			</div>
		);
	}
	if ( variations.length === 0 ) {
		return (
			<p className="wc-react-order-edit__product-picker-empty">
				{ __(
					'This product has no published variations.',
					'woocommerce'
				) }
			</p>
		);
	}

	return (
		<ul
			className="wc-react-order-edit__product-picker-results"
			aria-label={ __( 'Variation results', 'woocommerce' ) }
		>
			{ variations.map( ( variation ) => {
				const outOfStock = variation.stock_status === 'outofstock';
				const key = parent
					? selectionKey( parent.id, variation.id )
					: '';
				const isSelected = key ? selections.has( key ) : false;
				const rowClass = `wc-react-order-edit__product-picker-row-button${
					outOfStock
						? ' wc-react-order-edit__product-picker-row-button--oos'
						: ''
				}`;
				return (
					<li
						key={ variation.id }
						className="wc-react-order-edit__product-picker-row"
					>
						<label className={ rowClass }>
							<input
								type="checkbox"
								className="wc-react-order-edit__product-picker-row-checkbox"
								checked={ isSelected }
								onChange={ () => onToggleVariation( variation ) }
							/>
							<PickerThumb
								src={ variation.image?.src }
								alt={ variation.name || '' }
							/>
							<RowMain
								name={ variationAttributesSummary( variation ) }
								sku={ variation.sku }
								outOfStock={ outOfStock }
							/>
							<RowSide
								currencySymbol={ currencySymbol }
								price={ priceFor( variation ) }
							/>
						</label>
					</li>
				);
			} ) }
		</ul>
	);
}

interface RowMainProps {
	name: string;
	sku?: string;
	outOfStock: boolean;
}

function RowMain( { name, sku, outOfStock }: RowMainProps ) {
	return (
		<span className="wc-react-order-edit__product-picker-row-main">
			<span className="wc-react-order-edit__product-picker-row-name">
				{ name }
			</span>
			<span className="wc-react-order-edit__product-picker-row-meta">
				{ sku && (
					<span>
						{ __( 'SKU:', 'woocommerce' ) } { sku }
					</span>
				) }
				{ outOfStock && (
					<span className="wc-react-order-edit__product-picker-row-oos">
						{ __( 'Out of stock', 'woocommerce' ) }
					</span>
				) }
			</span>
		</span>
	);
}

interface RowSideProps {
	currencySymbol: string;
	price: string;
	action?: string;
}

function RowSide( { currencySymbol, price, action }: RowSideProps ) {
	return (
		<span className="wc-react-order-edit__product-picker-row-side">
			<span className="wc-react-order-edit__product-picker-row-price">
				{ currencySymbol }
				{ price || '—' }
			</span>
			{ action && (
				<span className="wc-react-order-edit__product-picker-row-action">
					{ action }
				</span>
			) }
		</span>
	);
}

function PickerThumb( { src, alt }: { src?: string; alt: string } ) {
	const [ failed, setFailed ] = useState( false );
	if ( ! src || failed ) {
		return (
			<span
				className="wc-react-order-edit__product-picker-thumb wc-react-order-edit__product-picker-thumb--placeholder"
				aria-hidden="true"
			/>
		);
	}
	return (
		<img
			className="wc-react-order-edit__product-picker-thumb"
			src={ src }
			alt={ alt }
			width={ 36 }
			height={ 36 }
			onError={ () => setFailed( true ) }
		/>
	);
}

/** Prefer regular_price (the catalog price) so the line item is seeded with
 * the un-discounted figure; fall back to `price` (which may be sale price)
 * and finally an empty string for the rare product with no price set. */
function priceFor(
	p: ProductSearchResult | ProductVariationResult
): string {
	return ( p.regular_price || p.price || '' ).toString();
}

/** Compose a variation's display name like "Size: Large, Color: Red". */
function variationAttributesSummary(
	variation: ProductVariationResult
): string {
	if ( variation.name ) {
		return variation.name;
	}
	const attrs = variation.attributes || [];
	if ( attrs.length === 0 ) {
		return sprintf(
			/* translators: %d: variation ID as a fallback label */
			__( 'Variation #%d', 'woocommerce' ),
			variation.id
		);
	}
	return attrs
		.map( ( a ) => `${ a.name ?? '' }: ${ a.option ?? '' }`.trim() )
		.filter( Boolean )
		.join( ', ' );
}

/** Build the name we write to the new line item — parent name with the
 * variation's attribute summary appended, so the items list reads e.g.
 * "Hoodie — Size: Large, Color: Red". */
function variationDisplayName(
	parent: ProductSearchResult,
	variation: ProductVariationResult
): string {
	const attrs = variationAttributesSummary( variation );
	return `${ parent.name } — ${ attrs }`;
}
