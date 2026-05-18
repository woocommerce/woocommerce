/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Badge, SelectControl } from '@wordpress/ui';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

type StockStatus = 'instock' | 'outofstock' | 'onbackorder';

function isValidStockStatus( value: string ): value is StockStatus {
	return (
		value === 'instock' || value === 'outofstock' || value === 'onbackorder'
	);
}

const stockStatusBadgeIntent: Record<
	StockStatus,
	React.ComponentProps< typeof Badge >[ 'intent' ]
> = {
	instock: 'none',
	outofstock: 'high',
	onbackorder: 'draft',
};

const fieldDefinition = {
	label: __( 'Stock', 'woocommerce' ),
	enableSorting: false,
	enableHiding: false,
	filterBy: {
		operators: [ 'is' ],
	},
	elements: [
		{ label: __( 'In stock', 'woocommerce' ), value: 'instock' },
		{ label: __( 'Out of stock', 'woocommerce' ), value: 'outofstock' },
		{ label: __( 'On backorder', 'woocommerce' ), value: 'onbackorder' },
	],
} satisfies Partial< Field< ProductEntityRecord > >;

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	...fieldDefinition,
	getValue: ( { item } ) => item.stock_status,
	render: ( { item } ) => {
		const status: StockStatus = isValidStockStatus( item.stock_status )
			? item.stock_status
			: 'instock';

		let label: string;
		if ( status === 'outofstock' ) {
			label = __( 'Out of stock', 'woocommerce' );
		} else if ( status === 'onbackorder' ) {
			label = __( 'On backorder', 'woocommerce' );
		} else if (
			item.manage_stock &&
			typeof item.stock_quantity === 'number' &&
			item.stock_quantity > 0
		) {
			label = sprintf(
				/* translators: %d: stock quantity. */
				__( '%d in stock', 'woocommerce' ),
				item.stock_quantity
			);
		} else {
			label = __( 'In stock', 'woocommerce' );
		}

		return (
			<div className="woocommerce-fields-field__stock">
				<Badge intent={ stockStatusBadgeIntent[ status ] }>
					{ label }
				</Badge>
			</div>
		);
	},
	Edit: ( { data, onChange, field } ) => {
		const options = field?.elements ?? [];
		const selectedOption = options.find(
			( option ) => option.value === data.stock_status
		);

		return (
			<SelectControl
				label={ __( 'Stock status', 'woocommerce' ) }
				value={ selectedOption }
				items={ options }
				onValueChange={ ( option ) => {
					const value = option?.value;

					if (
						typeof value === 'string' &&
						isValidStockStatus( value )
					) {
						onChange( {
							stock_status: value,
						} );
					}
				} }
			/>
		);
	},
};
