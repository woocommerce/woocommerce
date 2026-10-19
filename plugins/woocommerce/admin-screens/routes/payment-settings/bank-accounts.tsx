/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import type { DataFormControlProps, Field } from '@wordpress/dataviews';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Stack } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import {
	getSortCodeLabel,
	shouldDisplaySortCode,
} from '../../../client/admin/client/settings-payments/components/bank-accounts-list/utils';
import { getAccountCountry } from './entities';
import type { BankAccount, OfflineSettings } from './entities';

/**
 * Edit bank accounts as pending changes in the same settings record.
 */
export function BankAccounts( {
	data,
	onChange,
}: DataFormControlProps< OfflineSettings > ) {
	const accounts = data.account_details ?? [];
	const setAccounts = ( next: BankAccount[] ) => {
		onChange( { account_details: next } );
	};
	const update = ( index: number, edits: Partial< BankAccount > ) => {
		setAccounts(
			accounts.map( ( account, position ) =>
				position === index ? { ...account, ...edits } : account
			)
		);
	};
	const move = ( index: number, offset: number ) => {
		const next = [ ...accounts ];
		const account = next.splice( index, 1 )[ 0 ];
		if ( account ) {
			next.splice( index + offset, 0, account );
			setAccounts( next );
		}
	};

	return (
		<Stack direction="column" gap="md">
			{ accounts.map( ( account, index ) => {
				const country = getAccountCountry(
					account,
					data.default_country
				);
				const fields: Field< BankAccount >[] = [
					{
						id: 'country_code',
						type: 'text',
						label: __( 'Country', 'woocommerce' ),
						getValue: ( { item } ) =>
							getAccountCountry( item, data.default_country ),
						elements: Object.entries( data.countries ?? {} ).map(
							( [ value, label ] ) => ( { value, label } )
						),
					},
					{
						id: 'account_name',
						type: 'text',
						label: __( 'Account name', 'woocommerce' ),
						isValid: { required: true },
					},
					{
						id: 'account_number',
						type: 'text',
						label: __( 'Account number', 'woocommerce' ),
					},
					{
						id: 'bank_name',
						type: 'text',
						label: __( 'Bank name', 'woocommerce' ),
					},
					{
						id: 'sort_code',
						type: 'text',
						label: getSortCodeLabel( country ),
						isVisible: () => shouldDisplaySortCode( country ),
						isValid: { required: shouldDisplaySortCode( country ) },
					},
					{
						id: 'iban',
						type: 'text',
						label: __( 'IBAN', 'woocommerce' ),
					},
					{
						id: 'bic',
						type: 'text',
						label: __( 'BIC / Swift', 'woocommerce' ),
					},
				];
				return (
					<fieldset key={ index }>
						<legend>
							{ sprintf(
								/* translators: %d: bank account position. */ __(
									'Bank account %d',
									'woocommerce'
								),
								index + 1
							) }
						</legend>
						<DataForm
							data={ account }
							fields={ fields }
							form={ {
								fields: fields.map( ( field ) => field.id ),
							} }
							onChange={ ( edits ) => {
								update( index, edits );
							} }
						/>
						<Stack direction="row" gap="sm" wrap="wrap">
							<Button
								variant="outline"
								size="compact"
								disabled={ index === 0 }
								onClick={ () => {
									move( index, -1 );
								} }
							>
								{ __( 'Move up', 'woocommerce' ) }
							</Button>
							<Button
								variant="outline"
								size="compact"
								disabled={ index === accounts.length - 1 }
								onClick={ () => {
									move( index, 1 );
								} }
							>
								{ __( 'Move down', 'woocommerce' ) }
							</Button>
							<Button
								variant="outline"
								size="compact"
								onClick={ () => {
									setAccounts(
										accounts.filter(
											( _, position ) =>
												position !== index
										)
									);
								} }
							>
								{ __( 'Remove account', 'woocommerce' ) }
							</Button>
						</Stack>
					</fieldset>
				);
			} ) }
			<Button
				variant="outline"
				size="compact"
				onClick={ () => {
					setAccounts( [
						...accounts,
						{
							account_name: '',
							account_number: '',
							bank_name: '',
							sort_code: '',
							iban: '',
							bic: '',
							country_code: data.default_country ?? '',
						},
					] );
				} }
			>
				{ __( 'Add bank account', 'woocommerce' ) }
			</Button>
		</Stack>
	);
}
