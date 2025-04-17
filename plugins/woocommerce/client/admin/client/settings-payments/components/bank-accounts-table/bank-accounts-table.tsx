/**
 * External dependencies
 */
import { Button, Card, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu } from '@woocommerce/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { BankAccountModal } from './bank-account-modal';

const DEFAULT_STORE_COUNTRY = 'US'; // Ideally passed as a prop from backend

export interface BankAccount {
	id: string;
	account_name: string;
	account_number: string;
	bank_name: string;
	routing_number: string;
	sort_code: string;
	iban: string;
	bic: string;
}

function generateId() {
	return Math.random().toString( 36 ).substring( 2, 10 );
}
interface Props {
	accounts: BankAccount[];
	onChange: ( accounts: BankAccount[] ) => void;
	defaultCountry: string;
}

export const BankAccountsTable = ( {
	accounts,
	onChange,
	defaultCountry,
}: Props ) => {
	const [ selectedAccount, setSelectedAccount ] =
		useState< BankAccount | null >( null );
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const openModal = ( account: BankAccount | null = null ) => {
		setSelectedAccount( account );
		setIsModalOpen( true );
	};

	const handleSave = ( updated: BankAccount ) => {
		onChange(
			accounts.some( ( acc ) => acc.id === updated.id )
				? accounts.map( ( acc ) =>
						acc.id === updated.id ? updated : acc
				  )
				: [ ...accounts, { ...updated, id: generateId() } ]
		);
		setIsModalOpen( false );
	};

	const handleDelete = ( accountId: string ) => {
		onChange( accounts.filter( ( acc ) => acc.id !== accountId ) );
	};

	return (
		<Card>
			<table className="woocommerce-table">
				<thead>
					<tr>
						<th>{ __( 'Account Name', 'woocommerce' ) }</th>
						<th>{ __( 'Account Number', 'woocommerce' ) }</th>
						<th>{ __( 'Bank Name', 'woocommerce' ) }</th>
						<th />
					</tr>
				</thead>
				<tbody>
					{ accounts.map( ( account ) => (
						<tr key={ account.id }>
							<td>{ account.account_name }</td>
							<td>{ account.account_number }</td>
							<td>{ account.bank_name }</td>
							<td>
								<EllipsisMenu
									label={ __( 'Options', 'woocommerce' ) }
									placement={ 'bottom-right' }
									renderContent={ () => (
										<MenuGroup>
											<MenuItem
												onClick={ () =>
													openModal( account )
												}
											>
												{ __(
													'View / edit',
													'woocommerce'
												) }
											</MenuItem>
											<MenuItem
												isDestructive
												onClick={ () =>
													handleDelete( account.id )
												}
											>
												{ __(
													'Delete',
													'woocommerce'
												) }
											</MenuItem>
										</MenuGroup>
									) }
								/>
							</td>
						</tr>
					) ) }
					<tr>
						<td colSpan={ 4 }>
							<Button
								isSecondary
								onClick={ () => openModal( null ) }
							>
								{ __( '+ Add account', 'woocommerce' ) }
							</Button>
						</td>
					</tr>
				</tbody>
			</table>

			{ isModalOpen && (
				<BankAccountModal
					account={ selectedAccount }
					onClose={ () => setIsModalOpen( false ) }
					onSave={ handleSave }
					defaultCountry={ defaultCountry }
				/>
			) }
		</Card>
	);
};
