/**
 * External dependencies
 */
import { BaseControl, Button as ComponentsButton } from '@wordpress/components';
import { DataViews } from '@wordpress/dataviews';
import type {
	Action,
	DataFormControlProps,
	Field,
	View,
} from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/use-recommended-components -- Fine on this experimental screen until the repo enables allowUseWithCaution.
import { Button, Stack } from '@wordpress/ui';
import { useId, useMemo, useState } from 'react';

/**
 * Internal dependencies
 */
import type { BacsSettings, BankAccount } from '../types';
import { AccountForm } from './account-form';

interface ListedAccount extends BankAccount {
	index: number;
}

/**
 * Remove the list position from an account before it's stored.
 */
function toAccount( {
	index: _index,
	...account
}: ListedAccount ): BankAccount {
	return account;
}

/**
 * Move the account at one position to another.
 */
export function moveAccount(
	accounts: BankAccount[],
	from: number,
	to: number
): BankAccount[] {
	const moved = [ ...accounts ];
	const [ account ] = moved.splice( from, 1 );
	if ( account ) {
		moved.splice( to, 0, account );
	}
	return moved;
}

const LIST_FIELDS: Field< ListedAccount >[] = [
	{
		id: 'account_name',
		label: __( 'Account name', 'woocommerce' ),
		enableHiding: false,
		enableSorting: false,
	},
	{
		id: 'details',
		label: __( 'Account details', 'woocommerce' ),
		getValue: ( { item } ) => item.account_number || item.iban,
		enableHiding: false,
		enableSorting: false,
	},
	{
		id: 'bank_name',
		label: __( 'Bank name', 'woocommerce' ),
		enableHiding: false,
		enableSorting: false,
	},
];

/**
 * List the bank accounts, and add, edit, reorder or delete them.
 */
function AccountsEdit( {
	data,
	field,
	onChange,
}: DataFormControlProps< BacsSettings > ) {
	const id = useId();
	const value: unknown = field.getValue( { item: data } );
	const accounts = useMemo(
		() => ( Array.isArray( value ) ? ( value as BankAccount[] ) : [] ),
		[ value ]
	);
	const defaultCountry = data.bacs_accounts?.default_country ?? '';
	const [ isAdding, setIsAdding ] = useState( false );
	const [ editing, setEditing ] = useState< ListedAccount >();
	const [ view, setView ] = useState< View >( {
		type: 'table',
		titleField: 'account_name',
		fields: [ 'details', 'bank_name' ],
		perPage: 100,
		layout: {},
	} );
	const items = useMemo(
		() => accounts.map( ( account, index ) => ( { ...account, index } ) ),
		[ accounts ]
	);
	const update = ( next: BankAccount[] ) => {
		onChange( field.setValue( { item: data, value: next } ) );
	};
	const actions: Action< ListedAccount >[] = [
		{
			id: 'edit',
			label: __( 'Edit', 'woocommerce' ),
			isPrimary: true,
			callback: ( [ item ] ) => {
				setEditing( item );
			},
		},
		{
			id: 'move-up',
			label: __( 'Move up', 'woocommerce' ),
			isEligible: ( item ) => item.index > 0,
			callback: ( [ item ] ) => {
				if ( item ) {
					update(
						moveAccount( accounts, item.index, item.index - 1 )
					);
				}
			},
		},
		{
			id: 'move-down',
			label: __( 'Move down', 'woocommerce' ),
			isEligible: ( item ) => item.index < accounts.length - 1,
			callback: ( [ item ] ) => {
				if ( item ) {
					update(
						moveAccount( accounts, item.index, item.index + 1 )
					);
				}
			},
		},
		{
			id: 'delete',
			label: __( 'Delete', 'woocommerce' ),
			modalHeader: __( 'Delete bank account', 'woocommerce' ),
			RenderModal: ( { items: [ item ], closeModal } ) => (
				<Stack direction="column" gap="lg">
					<p>
						{ __(
							'Are you sure you want to delete this bank account?',
							'woocommerce'
						) }
					</p>
					<Stack direction="row" gap="sm" justify="end">
						<Button
							variant="outline"
							onClick={ () => closeModal?.() }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<ComponentsButton
							variant="primary"
							isDestructive
							__next40pxDefaultSize
							onClick={ () => {
								if ( item ) {
									update(
										accounts.filter(
											( _account, index ) =>
												index !== item.index
										)
									);
								}
								closeModal?.();
							} }
						>
							{ __( 'Delete', 'woocommerce' ) }
						</ComponentsButton>
					</Stack>
				</Stack>
			),
		},
	];
	return (
		<BaseControl
			id={ id }
			label={ field.label }
			// The card already shows the same heading.
			hideLabelFromVision
			help={ field.description }
			__nextHasNoMarginBottom
		>
			<Stack direction="column" gap="md">
				{ items.length > 0 && (
					<DataViews
						data={ items }
						fields={ LIST_FIELDS }
						view={ view }
						onChangeView={ setView }
						actions={ actions }
						getItemId={ ( item ) => String( item.index ) }
						paginationInfo={ {
							totalItems: items.length,
							totalPages: 1,
						} }
						defaultLayouts={ { table: {} } }
					>
						<DataViews.Layout />
					</DataViews>
				) }
				<Stack direction="row">
					<Button
						variant="outline"
						onClick={ () => {
							setIsAdding( true );
						} }
					>
						{ __( 'Add account', 'woocommerce' ) }
					</Button>
				</Stack>
			</Stack>
			{ isAdding && (
				<AccountForm
					defaultCountry={ defaultCountry }
					onClose={ () => {
						setIsAdding( false );
					} }
					onSave={ ( account ) => {
						update( [ ...accounts, account ] );
						setIsAdding( false );
					} }
				/>
			) }
			{ editing && (
				<AccountForm
					account={ toAccount( editing ) }
					defaultCountry={ defaultCountry }
					onClose={ () => {
						setEditing( undefined );
					} }
					onSave={ ( account ) => {
						update(
							accounts.map( ( current, index ) =>
								index === editing.index ? account : current
							)
						);
						setEditing( undefined );
					} }
				/>
			) }
		</BaseControl>
	);
}

export const field: Field< BacsSettings > = {
	id: 'accounts',
	label: __( 'Account details', 'woocommerce' ),
	description: __(
		'These account details will be displayed within the order thank you page and confirmation email.',
		'woocommerce'
	),
	getValue: ( { item } ) =>
		Array.isArray( item.bacs_accounts?.accounts )
			? item.bacs_accounts.accounts
			: [],
	setValue: ( { value } ) => ( {
		bacs_accounts: { accounts: value as BankAccount[] },
	} ),
	Edit: AccountsEdit,
};
