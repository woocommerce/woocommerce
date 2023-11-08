/**
 * External dependencies
 */
import { Table, TablePlaceholder } from '@woocommerce/components';
import {
	TableHeader,
	TableRow,
} from '@woocommerce/components/build-types/table/types';
import { __ } from '@wordpress/i18n';

const tableHeadersDefault = [
	{
		key: 'name',
		label: __( 'Name', 'woocommerce' ),
	},
	{
		key: 'expiry',
		label: __( 'Expiry/Renewal date', 'woocommerce' ),
	},
	{
		key: 'autoRenew',
		label: __( 'Auto-renew', 'woocommerce' ),
	},
	{
		key: 'version',
		label: __( 'Version', 'woocommerce' ),
	},
];

function SubscriptionsTable( props: {
	rows?: TableRow[][];
	headers: TableHeader[];
	isLoading: boolean;
} ) {
	if ( props.isLoading ) {
		return (
			<TablePlaceholder
				caption={ __( 'Loading your subscriptions', 'woocommerce' ) }
				headers={ props.headers }
			/>
		);
	}

	const headersWithClasses = props.headers.map( ( header ) => {
		return {
			...header,
			cellClassName:
				'woocommerce-marketplace__my-subscriptions__table__header--' +
				header.key,
		};
	} );

	return (
		<Table
			className="woocommerce-marketplace__my-subscriptions__table"
			headers={ headersWithClasses }
			rows={ props.rows }
		/>
	);
}

export function InstalledSubscriptionsTable( props: {
	rows?: TableRow[][];
	isLoading: boolean;
} ) {
	const headers = [
		...tableHeadersDefault,
		{
			key: 'actions',
			label: __( 'Actions', 'woocommerce' ),
		},
	];

	return (
		<SubscriptionsTable
			rows={ props.rows }
			isLoading={ props.isLoading }
			headers={ headers }
		/>
	);
}

export function AvailableSubscriptionsTable( props: {
	rows?: TableRow[][];
	isLoading: boolean;
} ) {
	const headers = [
		...tableHeadersDefault,
		{
			key: 'actions',
			label: __( 'Actions', 'woocommerce' ),
		},
	];

	return (
		<SubscriptionsTable
			rows={ props.rows }
			isLoading={ props.isLoading }
			headers={ headers }
		/>
	);
}
