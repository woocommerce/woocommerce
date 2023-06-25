/**
 * External dependencies
 */
import { TableCard } from '@woocommerce/components';
import { useState, createElement } from '@wordpress/element';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { headers, rows, summary } from './index';

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

export const Basic = () => <TableCardExample />;
export const Actions = () => <TableCardWithActionsExample />;

export default {
	title: 'WooCommerce Admin/components/TableCard',
	component: TableCard,
};
