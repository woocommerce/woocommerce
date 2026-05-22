/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
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
		if ( ! isValidStockStatus( item.stock_status ) ) {
			return null;
		}

		const status = item.stock_status;

		const staticLabels: Partial< Record< StockStatus, string > > = {
			outofstock: __( 'Out of stock', 'woocommerce' ),
			onbackorder: __( 'On backorder', 'woocommerce' ),
		};

		const qty = item.stock_quantity;
		const label =
			staticLabels[ status ] ??
			( item.manage_stock && Number.isFinite( qty ) && ( qty as number ) > 0
				? sprintf(
						/* translators: %d: stock quantity number. */
						_n( '%d in stock', '%d in stock', qty as number, 'woocommerce' ),
						qty
				  )
				: __( 'In stock', 'woocommerce' ) );

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
