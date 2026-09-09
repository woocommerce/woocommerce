/**
 * External dependencies
 */
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useProviderBalances } from '../data/use-provider-balances';
import { useAmountFormatter, AmountFormatter } from '../utils/format-amount';
import { EMPTY_VALUE } from '../utils/format-date';
import { ProviderCell } from '../components/provider-cell';
import type { Balance, FinanceProvider } from '../types';

const supportsBalance = ( provider: FinanceProvider ): boolean =>
	provider.data_types.some( ( dataType ) => dataType.type === 'balance' );

type ProviderRowProps = {
	provider: FinanceProvider;
	formatAmount: AmountFormatter;
};

const BalanceCells = ( {
	balances,
	formatAmount,
}: {
	balances: Balance[];
	formatAmount: AmountFormatter;
} ) => (
	<>
		<td>
			{ balances.map( ( balance ) => (
				<div key={ balance.currency }>
					{ formatAmount( balance.amount, balance.currency ) }
				</div>
			) ) }
		</td>
		<td>
			{ balances.map( ( balance ) => (
				<div key={ balance.currency }>
					{ balance.available_amount === null
						? EMPTY_VALUE
						: formatAmount(
								balance.available_amount,
								balance.currency
						  ) }
				</div>
			) ) }
		</td>
		<td className="woocommerce-finance-provider-table__actions">
			{ balances.map( ( balance ) =>
				balance.payout_link ? (
					<Button
						key={ balance.currency }
						variant="secondary"
						href={ balance.payout_link.url }
						target="_blank"
						rel="noreferrer"
					>
						{ balance.payout_link.title }
					</Button>
				) : null
			) }
		</td>
	</>
);

const ProviderRow = ( { provider, formatAmount }: ProviderRowProps ) => {
	const hasBalance = supportsBalance( provider );
	const { balances, isLoading, error } = useProviderBalances(
		hasBalance ? provider.provider_id : null
	);

	let cells;
	if ( ! hasBalance ) {
		cells = (
			<td
				colSpan={ 3 }
				className="woocommerce-finance-provider-table__note"
			>
				{ __( 'Balance not available', 'woocommerce' ) }
			</td>
		);
	} else if ( isLoading ) {
		cells = (
			<td colSpan={ 3 }>
				<Spinner />
			</td>
		);
	} else if ( error ) {
		cells = (
			<td
				colSpan={ 3 }
				className="woocommerce-finance-provider-table__note"
			>
				{ error.message }
			</td>
		);
	} else if ( balances.length === 0 ) {
		cells = (
			<td
				colSpan={ 3 }
				className="woocommerce-finance-provider-table__note"
			>
				{ EMPTY_VALUE }
			</td>
		);
	} else {
		cells = (
			<BalanceCells balances={ balances } formatAmount={ formatAmount } />
		);
	}

	return (
		<tr>
			<th scope="row">
				<ProviderCell
					provider={ provider }
					fallbackId={ provider.provider_id }
				/>
			</th>
			{ cells }
		</tr>
	);
};

type ProviderTableProps = {
	providers: FinanceProvider[];
};

/**
 * One row per connected provider with its balance, available funds and payout link per currency.
 */
export const ProviderTable = ( { providers }: ProviderTableProps ) => {
	const formatAmount = useAmountFormatter();

	return (
		<div className="woocommerce-finance-provider-table__scroll">
			<table className="woocommerce-finance-provider-table">
				<thead>
					<tr>
						<th scope="col">{ __( 'Provider', 'woocommerce' ) }</th>
						<th scope="col">{ __( 'Balance', 'woocommerce' ) }</th>
						<th scope="col">
							{ __( 'Available funds', 'woocommerce' ) }
						</th>
						<th scope="col">
							<span className="screen-reader-text">
								{ __( 'Actions', 'woocommerce' ) }
							</span>
						</th>
					</tr>
				</thead>
				<tbody>
					{ providers.map( ( provider ) => (
						<ProviderRow
							key={ provider.provider_id }
							provider={ provider }
							formatAmount={ formatAmount }
						/>
					) ) }
				</tbody>
			</table>
		</div>
	);
};
