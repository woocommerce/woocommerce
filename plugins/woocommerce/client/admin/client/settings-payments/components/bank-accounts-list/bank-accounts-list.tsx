/**
 * External dependencies
 */
import { Button, MenuGroup, MenuItem, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu } from '@woocommerce/components';
import { useState, useEffect } from 'react';

/**
 * Internal dependencies
 */
import { BankAccount } from './types';
import { BankAccountModal } from './bank-account-modal';
import { generateId } from './utils';
import {
	DefaultDragHandle,
	SortableContainer,
	SortableItem,
} from '~/settings-payments/components/sortable';
import './bank-account-list.scss';

interface BankAccountItem extends BankAccount {
	id: string;
}

/**
 * Props for BankAccountsList component.
 *
 */
interface Props {
	accounts: BankAccount[];
	onChange: ( accounts: BankAccount[] ) => void;
	defaultCountry: string;
}

/**
 * BankAccountsList component renders a sortable list of bank accounts,
 * allowing users to add, edit, reorder, and delete accounts.
 *
 * @param {Props} props Component props.
 * @return {Element} The rendered component.
 */
export const BankAccountsList = ( {
	accounts,
	onChange,
	defaultCountry,
}: Props ) => {
	const [ accountsWithIds, setAccountsWithIds ] = useState<
		BankAccountItem[]
	>( () =>
		accounts.map( ( account ) => ( { ...account, id: generateId() } ) )
	);

	useEffect( () => {
		if ( accounts.length && accountsWithIds.length === 0 ) {
			setAccountsWithIds(
				accounts.map( ( account ) => ( {
					...account,
					id: generateId(),
				} ) )
			);
		}
	}, [ accounts, accountsWithIds.length ] );

	const [ selectedAccount, setSelectedAccount ] =
		useState< BankAccountItem | null >( null );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ accountToDelete, setAccountToDelete ] =
		useState< BankAccountItem | null >( null );

	/**
	 * Opens the bank account modal for adding or editing an account.
	 *
	 * @param {BankAccountItem | null} account The account to edit, or null to add a new one.
	 */
	const openModal = ( account: BankAccountItem | null = null ) => {
		setSelectedAccount( account );
		setIsModalOpen( true );
	};

	/**
	 * Handles saving of a bank account, either updating an existing one or adding a new one.
	 *
	 * @param {BankAccount} updated The updated or new bank account.
	 */
	const handleSave = ( updated: BankAccount ) => {
		const existingIndex = accountsWithIds.findIndex(
			( acc ) => acc.id === selectedAccount?.id
		);

		let newAccounts;
		if ( existingIndex !== -1 ) {
			// Update existing
			newAccounts = [ ...accountsWithIds ];
			newAccounts[ existingIndex ] = {
				...updated,
				id: selectedAccount?.id || generateId(),
			};
		} else {
			// Add new
			newAccounts = [
				...accountsWithIds,
				{ ...updated, id: generateId() },
			];
		}

		setAccountsWithIds( newAccounts );
		onChange( newAccounts.map( ( { id, ...rest } ) => rest ) );
		setIsModalOpen( false );
	};

	/**
	 * Confirms and deletes the selected bank account.
	 */
	const confirmDelete = () => {
		if ( ! accountToDelete ) return;
		const newAccounts = accountsWithIds.filter(
			( acc ) => acc.id !== accountToDelete.id
		);
		setAccountsWithIds( newAccounts );
		onChange( newAccounts.map( ( { id, ...rest } ) => rest ) );
		setAccountToDelete( null );
	};

	/**
	 * Updates the ordering of bank accounts after drag-and-drop sorting.
	 *
	 * @param {BankAccountItem[]} newAccounts The reordered list of bank accounts.
	 */
	const handleUpdateOrdering = ( newAccounts: BankAccountItem[] ) => {
		setAccountsWithIds( newAccounts );
		onChange( newAccounts.map( ( { id, ...rest } ) => rest ) );
	};

	return (
		<>
			<SortableContainer< BankAccountItem >
				items={ accountsWithIds }
				className={ 'bank-accounts__list' }
				setItems={ handleUpdateOrdering }
			>
				<div className="bank-accounts__list-header">
					<div className="bank-accounts__list-item-inner">
						<div className="bank-accounts__list-item-before" />
						<div className="bank-accounts__list-item-text">
							<div>Account Name</div>
							<div>Account Number</div>
							<div>Bank Name</div>
						</div>
						<div className="bank-accounts__list-item-after" />
					</div>
				</div>
				{ accountsWithIds.map( ( account, index ) => (
					<SortableItem
						key={ account.id }
						id={ account.id }
						className={ `bank-accounts__list-item${
							index === 0 ? ' first-item' : ''
						}` }
					>
						<div className="bank-accounts__list-item-inner">
							<div className="bank-accounts__list-item-before">
								<DefaultDragHandle />
							</div>
							<div className="bank-accounts__list-item-text">
								<div>{ account.account_name }</div>
								<div>{ account.account_number }</div>
								<div>{ account.bank_name }</div>
							</div>
							<div className="bank-accounts__list-item-after">
								<EllipsisMenu
									label={ __( 'Options', 'woocommerce' ) }
									placement={ 'bottom-right' }
									renderContent={ ( {
										onClose = () => {},
									} ) => (
										<MenuGroup>
											<MenuItem
												role="menuitem"
												onClick={ () => {
													onClose();
													openModal( account );
												} }
											>
												{ __(
													'View / edit',
													'woocommerce'
												) }
											</MenuItem>
											<MenuItem
												isDestructive
												onClick={ () => {
													onClose();
													setAccountToDelete(
														account
													);
												} }
											>
												{ __(
													'Delete',
													'woocommerce'
												) }
											</MenuItem>
										</MenuGroup>
									) }
								/>
							</div>
						</div>
					</SortableItem>
				) ) }
				<li
					className={ `bank-accounts__list-item action${
						accountsWithIds.length === 0 ? ' first-item' : ''
					}` }
				>
					<Button
						variant={ 'secondary' }
						onClick={ () => openModal( null ) }
					>
						{ __( '+ Add account', 'woocommerce' ) }
					</Button>
				</li>
			</SortableContainer>

			{ isModalOpen && (
				<BankAccountModal
					account={ selectedAccount }
					onClose={ () => setIsModalOpen( false ) }
					onSave={ handleSave }
					defaultCountry={ defaultCountry }
				/>
			) }

			{ accountToDelete && (
				<Modal
					title={ __( 'Delete account', 'woocommerce' ) }
					onRequestClose={ () => setAccountToDelete( null ) }
					shouldCloseOnClickOutside={ false }
				>
					<p>
						{ __(
							'Are you sure you want to delete this bank account?',
							'woocommerce'
						) }
					</p>
					<div
						style={ {
							display: 'flex',
							justifyContent: 'flex-end',
							gap: '8px',
							marginTop: '16px',
						} }
					>
						<Button
							variant="secondary"
							onClick={ () => setAccountToDelete( null ) }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ confirmDelete }
						>
							{ __( 'Delete', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</>
	);
};
