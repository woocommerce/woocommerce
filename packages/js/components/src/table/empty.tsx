/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import React from 'react';

type EmptyTableProps = {
	children: React.ReactNode;

	/** An integer with the number of rows the box should occupy. */
	numberOfRows: number;
};

/**
 * `EmptyTable` displays a blank space with an optional message passed as a children node
 * with the purpose of replacing a table with no rows.
 * It mimics the same height a table would have according to the `numberOfRows` prop.
 */
const EmptyTable = ( { children, numberOfRows = 5 }: EmptyTableProps ) => {
	return (
		<div
			className="woocommerce-table is-empty"
			style={
				{ '--number-of-rows': numberOfRows } as React.CSSProperties
			}
		>
			{ children }
		</div>
	);
};

export default EmptyTable;
