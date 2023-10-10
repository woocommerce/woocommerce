/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Table, TablePlaceholder } from '@woocommerce/components';
import {
	TableHeader,
	TableRow,
} from '@woocommerce/components/build-types/table/types';

const tableHeadersDefault = [
	{
		key: 'name',
		label: __( 'Name', 'woocommerce' ),
	},
	{
		key: 'status',
		label: __( 'Status', 'woocommerce' ),
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

	return (
		<Table
			className="woocommerce-marketplace__my-subscriptions__table"
			headers={ props.headers }
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
			key: 'activated',
			label: __( 'Activated', 'woocommerce' ),
		},
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
			key: 'install',
			label: __( 'Install', 'woocommerce' ),
		},
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
