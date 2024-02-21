/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { createRef, useEffect, useRef } from '@wordpress/element';
import CartLineItemRow from '@woocommerce/base-components/cart-checkout/cart-line-items-table/cart-line-item-row';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { CartResponseItem } from '@woocommerce/type-defs/cart-response';
import type { RefObject } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import SingleCartLineItemBlock from '../single-cart-line-item-block/block';

const setRefs = ( lineItems: CartResponseItem[] ) => {
	const refs = {} as Record< string, RefObject< HTMLTableRowElement > >;
	lineItems.forEach( ( { key } ) => {
		refs[ key ] = createRef();
	} );
	return refs;
};

const placeholderRows = [ ...Array( 3 ) ].map( ( _x, i ) => (
	<CartLineItemRow lineItem={ {} } key={ i } />
) );

const Block = ( { className }: { className: string } ): JSX.Element => {
	const { cartItems: lineItems, cartIsLoading: isLoading } = useStoreCart();

	const tableRef = useRef< HTMLTableElement | null >( null );
	const rowRefs = useRef( setRefs( lineItems ) );
	useEffect( () => {
		rowRefs.current = setRefs( lineItems );
	}, [ lineItems ] );

	const onRemoveRow = ( nextItemKey: string | null ) => () => {
		if (
			rowRefs?.current &&
			nextItemKey &&
			rowRefs.current[ nextItemKey ].current instanceof HTMLElement
		) {
			( rowRefs.current[ nextItemKey ].current as HTMLElement ).focus();
		} else if ( tableRef.current instanceof HTMLElement ) {
			tableRef.current.focus();
		}
	};

	const products = isLoading
		? placeholderRows
		: lineItems.map( ( lineItem, i ) => {
				const nextItemKey =
					lineItems.length > i + 1 ? lineItems[ i + 1 ].key : null;
				return (
					<SingleCartLineItemBlock
						key={ lineItem.key }
						lineItem={ lineItem }
						onRemove={ onRemoveRow( nextItemKey ) }
						ref={ rowRefs.current[ lineItem.key ] }
						tabIndex={ -1 }
					/>
				);
		  } );

	return (
		<table
			className={ classnames( 'wc-block-cart-items', className ) }
			ref={ tableRef }
			tabIndex={ -1 }
		>
			<thead>
				<tr className="wc-block-cart-items__header">
					<th className="wc-block-cart-items__header-image">
						<span>{ __( 'Product', 'woocommerce' ) }</span>
					</th>
					<th className="wc-block-cart-items__header-product">
						<span>{ __( 'Details', 'woocommerce' ) }</span>
					</th>
					<th className="wc-block-cart-items__header-total">
						<span>{ __( 'Total', 'woocommerce' ) }</span>
					</th>
				</tr>
			</thead>
			<tbody>{ products }</tbody>
		</table>
	);
};

export default Block;
