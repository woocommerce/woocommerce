/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { CartResponseItem } from '@woocommerce/types';
import {
	createRef,
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { startLayoutChange } from '@woocommerce/base-utils/layout-transition';
import type { RefObject } from 'react';
import { CartLineItemsCartSkeleton } from '@woocommerce/base-components/skeleton/patterns/cart-line-items';
import { DelayedContentWithSkeleton } from '@woocommerce/base-components/delayed-content-with-skeleton';

/**
 * Internal dependencies
 */
import CartLineItemRow from './cart-line-item-row';
import './style.scss';

interface CartLineItemsTableProps {
	lineItems: CartResponseItem[];
	isLoading: boolean;
	className?: string;
}

const setRefs = ( lineItems: CartResponseItem[] ) => {
	const refs = {} as Record< string, RefObject< HTMLTableRowElement > >;
	lineItems.forEach( ( { key } ) => {
		refs[ key ] = createRef();
	} );
	return refs;
};

// Cart rows and the blocks after the line items, such as cross-sells, so they slide up when a row leaves.
const CART_SECTIONS =
	'.wc-block-cart-items__row, .wp-block-woocommerce-cart-items-block > :not(.wp-block-woocommerce-cart-line-items-block)';

/**
 * Shows `lineItems`, but applies removals inside a layout transition, so the removed row wipes out and the rows
 * below slide up. Changes arriving before the transition applies are folded into it.
 */
const useLineItemsWithLayoutTransition = ( lineItems: CartResponseItem[] ) => {
	const [ displayedItems, setDisplayedItems ] = useState( lineItems );
	const latestItems = useRef( lineItems );
	latestItems.current = lineItems;
	const keys = lineItems.map( ( { key } ) => key );
	const isRemoving = displayedItems.some(
		( { key } ) => ! keys.includes( key )
	);
	if ( ! isRemoving && displayedItems !== lineItems ) {
		setDisplayedItems( lineItems );
	}

	useLayoutEffect( () => {
		if ( isRemoving ) {
			startLayoutChange( () => setDisplayedItems( latestItems.current ), {
				sections: CART_SECTIONS,
			} );
		}
	}, [ isRemoving ] );

	return displayedItems;
};

const CartLineItemsTable = ( {
	lineItems: currentLineItems = [],
	isLoading = false,
	className,
}: CartLineItemsTableProps ): JSX.Element => {
	const lineItems = useLineItemsWithLayoutTransition( currentLineItems );
	const tableRef = useRef< HTMLTableElement | null >( null );
	const rowRefs = useRef( setRefs( lineItems ) );
	useEffect( () => {
		rowRefs.current = setRefs( lineItems );
	}, [ lineItems ] );

	const onRemoveRow = ( nextItemKey: string | null ) => () => {
		requestAnimationFrame( () => {
			if (
				rowRefs?.current &&
				nextItemKey &&
				rowRefs.current[ nextItemKey ].current instanceof HTMLElement
			) {
				(
					rowRefs.current[ nextItemKey ].current as HTMLElement
				 ).focus();
			} else if ( tableRef.current instanceof HTMLElement ) {
				tableRef.current.focus();
			}
		} );
	};

	const products = (
		<DelayedContentWithSkeleton
			isLoading={ isLoading }
			skeleton={ <CartLineItemsCartSkeleton /> }
		>
			<>
				{ lineItems.map( ( lineItem, i ) => {
					const nextItemKey =
						lineItems.length > i + 1
							? lineItems[ i + 1 ].key
							: null;
					return (
						<CartLineItemRow
							key={ lineItem.key }
							lineItem={ lineItem }
							onRemove={ onRemoveRow( nextItemKey ) }
							ref={ rowRefs.current[ lineItem.key ] }
							tabIndex={ -1 }
						/>
					);
				} ) }
			</>
		</DelayedContentWithSkeleton>
	);

	return (
		<table
			className={ clsx( 'wc-block-cart-items', className ) }
			ref={ tableRef }
			tabIndex={ -1 }
		>
			<caption className="screen-reader-text">
				<h2>{ __( 'Products in cart', 'woocommerce' ) }</h2>
			</caption>
			<thead>
				<tr className="wc-block-cart-items__header">
					{ /* Decorative image column, hidden from screen readers (see cart-line-item-row.tsx). */ }
					<th
						scope="col"
						aria-hidden="true"
						className="wc-block-cart-items__header-image"
					>
						<span>{ __( 'Product', 'woocommerce' ) }</span>
					</th>
					<th
						scope="col"
						className="wc-block-cart-items__header-product"
					>
						<span className="screen-reader-text">
							{ __( 'Details', 'woocommerce' ) }
						</span>
					</th>
					<th
						scope="col"
						className="wc-block-cart-items__header-total"
					>
						<span>{ __( 'Total', 'woocommerce' ) }</span>
					</th>
				</tr>
			</thead>
			<tbody>{ products }</tbody>
		</table>
	);
};

export default CartLineItemsTable;
