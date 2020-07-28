/**
 * External dependencies
 */
import {
	Card,
	EmptyTable,
	H,
	Section,
	Table,
	TableCard,
	TablePlaceholder,
	TableSummary,
} from '@woocommerce/components';

/**
 * External dependencies
 */
import { withState } from '@wordpress/compose';

const headers = [
	{ key: 'month', label: 'Month' },
	{ key: 'orders', label: 'Orders' },
	{ key: 'revenue', label: 'Revenue' },
];
const rows = [
	[
		{ display: 'January', value: 1 },
		{ display: 10, value: 10 },
		{ display: '$530.00', value: 530 },
	],
	[
		{ display: 'February', value: 2 },
		{ display: 13, value: 13 },
		{ display: '$675.00', value: 675 },
	],
	[
		{ display: 'March', value: 3 },
		{ display: 9, value: 9 },
		{ display: '$460.00', value: 460 },
	],
];
const summary = [
	{ label: 'Gross Income', value: '$830.00' },
	{ label: 'Taxes', value: '$96.32' },
	{ label: 'Shipping', value: '$50.00' },
];

export default withState( {
	query: {
		paged: 1,
	},
} )( ( { query, setState } ) => (
	<div>
		<H>TableCard</H>
		<Section component={ false }>
			<TableCard
				title="Revenue Last Week"
				rows={ rows }
				headers={ headers }
				onQueryChange={ ( param ) => ( value ) =>
					setState( {
						query: {
							[ param ]: value,
						},
					} ) }
				query={ query }
				rowsPerPage={ 7 }
				totalRows={ 10 }
				summary={ summary }
			/>
		</Section>

		<H>Table only</H>
		<Section component={ false }>
			<Card className="woocommerce-analytics__card">
				<Table
					caption="Revenue Last Week"
					rows={ rows }
					headers={ headers }
				/>
			</Card>
		</Section>

		<H>Summary only</H>
		<Section component={ false }>
			<TableSummary data={ summary } />
		</Section>

		<H>Placeholder</H>
		<Section component={ false }>
			<Card className="woocommerce-analytics__card">
				<TablePlaceholder
					caption="Revenue Last Week"
					headers={ headers }
				/>
			</Card>
		</Section>

		<H>Empty Table</H>
		<Section component={ false }>
			<EmptyTable>There are no entries.</EmptyTable>
		</Section>
	</div>
) );
