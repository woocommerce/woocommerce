/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';
import { useState, createElement } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { headers, rows, summary } from './index';

// Create headers with many columns to trigger horizontal scrolling
const wideHeaders = [
	{ key: 'month', label: 'Month' },
	{ key: 'orders', label: 'Orders' },
	{ key: 'revenue', label: 'Revenue' },
	{ key: 'profit', label: 'Profit' },
	{ key: 'taxes', label: 'Taxes' },
	{ key: 'shipping', label: 'Shipping' },
	{ key: 'discounts', label: 'Discounts' },
	{ key: 'refunds', label: 'Refunds' },
	{ key: 'fees', label: 'Fees' },
	{ key: 'net', label: 'Net Revenue' },
];

// Create rows with many columns
const wideRows = [
	[
		{ display: 'January', value: 1 },
		{ display: 10, value: 10 },
		{ display: '$530.00', value: 530 },
		{ display: '$450.00', value: 450 },
		{ display: '$80.00', value: 80 },
		{ display: '$25.00', value: 25 },
		{ display: '$15.00', value: 15 },
		{ display: '$0.00', value: 0 },
		{ display: '$5.00', value: 5 },
		{ display: '$405.00', value: 405 },
	],
	[
		{ display: 'February', value: 2 },
		{ display: 13, value: 13 },
		{ display: '$675.00', value: 675 },
		{ display: '$580.00', value: 580 },
		{ display: '$95.00', value: 95 },
		{ display: '$30.00', value: 30 },
		{ display: '$20.00', value: 20 },
		{ display: '$0.00', value: 0 },
		{ display: '$8.00', value: 8 },
		{ display: '$517.00', value: 517 },
	],
	[
		{ display: 'March', value: 3 },
		{ display: 9, value: 9 },
		{ display: '$460.00', value: 460 },
		{ display: '$390.00', value: 390 },
		{ display: '$70.00', value: 70 },
		{ display: '$22.00', value: 22 },
		{ display: '$18.00', value: 18 },
		{ display: '$0.00', value: 0 },
		{ display: '$6.00', value: 6 },
		{ display: '$344.00', value: 344 },
	],
];

const TableCardExample = () => {
	const [ { query }, setState ] = useState( {
		query: {
			paged: 1,
		},
	} );
	return (
		<TableCard
			title="Revenue last week"
			rows={ rows }
			headers={ headers }
			onQueryChange={ ( param ) => ( value ) =>
				setState( {
					// @ts-expect-error: ignore for storybook
					query: {
						[ param ]: value,
					},
				} ) }
			query={ query }
			rowsPerPage={ 7 }
			totalRows={ 10 }
			summary={ summary }
		/>
	);
};

const TableCardWithActionsExample = () => {
	const [ { query }, setState ] = useState( {
		query: {
			paged: 1,
		},
	} );

	const [ action1Text, setAction1Text ] = useState( 'Action 1' );
	const [ action2Text, setAction2Text ] = useState( 'Action 2' );

	return (
		<TableCard
			actions={ [
				<Button
					key={ 0 }
					onClick={ () => {
						setAction1Text( 'Action 1 Clicked' );
					} }
				>
					{ action1Text }
				</Button>,
				<Button
					key={ 0 }
					onClick={ () => {
						setAction2Text( 'Action 2 Clicked' );
					} }
				>
					{ action2Text }
				</Button>,
			] }
			title="Revenue last week"
			rows={ rows }
			headers={ headers }
			onQueryChange={ ( param ) => ( value ) =>
				setState( {
					// @ts-expect-error: ignore for storybook
					query: {
						[ param ]: value,
					},
				} ) }
			query={ query }
			rowsPerPage={ 7 }
			totalRows={ 10 }
			summary={ summary }
		/>
	);
};

const TableCardWithTablePrefaceExample = () => {
	const [ { query }, setState ] = useState( {
		query: {
			paged: 1,
		},
	} );

	const [ showNotice, setShowNotice ] = useState( true );

	return (
		<TableCard
			title="Revenue last week"
			rows={ rows }
			headers={ headers }
			tablePreface={
				showNotice && (
					<Notice
						status="info"
						isDismissible={ true }
						onRemove={ () => setShowNotice( false ) }
					>
						This is an important notice about the table
					</Notice>
				)
			}
			onQueryChange={ ( param ) => ( value ) =>
				setState( {
					// @ts-expect-error: ignore for storybook
					query: {
						[ param ]: value,
					},
				} ) }
			query={ query }
			rowsPerPage={ 7 }
			totalRows={ 10 }
			summary={ summary }
		/>
	);
};

const TableCardWideExample = () => {
	const [ { query }, setState ] = useState( {
		query: {
			paged: 1,
		},
	} );
	return (
		<TableCard
			title="Revenue with many columns (test horizontal scroll)"
			rows={ wideRows }
			headers={ wideHeaders }
			onQueryChange={ ( param ) => ( value ) =>
				setState( {
					// @ts-expect-error: ignore for storybook
					query: {
						[ param ]: value,
					},
				} ) }
			query={ query }
			rowsPerPage={ 7 }
			totalRows={ 10 }
			summary={ summary }
		/>
	);
};

export const Basic = () => <TableCardExample />;
export const Actions = () => <TableCardWithActionsExample />;
export const TablePreface = () => <TableCardWithTablePrefaceExample />;
export const WideTable = () => <TableCardWideExample />;

export default {
	title: 'Components/TableCard',
	component: TableCard,
};
