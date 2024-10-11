/**
 * External dependencies
 */
import { EmptyTable, Table, TablePlaceholder } from '@woocommerce/components';
import {
	TableHeader,
	TableRow,
} from '@woocommerce/components/build-types/table/types';
import { getNewPath } from '@woocommerce/navigation';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MARKETPLACE_PATH } from '../../constants';

const tableHeadersDefault = [
	{
		key: 'name',
		label: __( 'Name', 'woocommerce' ),
	},
	{
		key: 'expiry',
		label: __( 'Expires/Renews on', 'woocommerce' ),
	},
	{
		key: 'subscription',
		label: __( 'Subscription', 'woocommerce' ),
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

	if ( ! props.isLoading && ( ! props.rows || props.rows.length === 0 ) ) {
		const marketplaceBrowseURL = getNewPath( {}, MARKETPLACE_PATH, {} );
		const noInstalledSubscriptionsHTML = createInterpolateElement(
			__(
				'No extensions or themes installed. <a>Browse the Marketplace</a>',
				'woocommerce'
			),
			{
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				a: <a href={ marketplaceBrowseURL } />,
			}
		);

		return (
			<EmptyTable numberOfRows={ 4 }>
				{ noInstalledSubscriptionsHTML }
			</EmptyTable>
		);
	}

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
