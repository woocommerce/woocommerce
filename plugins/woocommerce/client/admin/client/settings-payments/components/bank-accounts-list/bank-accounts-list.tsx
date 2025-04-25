/**
 * External dependencies
 */
import { Button, MenuGroup, MenuItem, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu } from '@woocommerce/components';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { BankAccount } from './types';
import { BankAccountModal } from './bank-account-modal';
import {
	DefaultDragHandle,
	SortableContainer,
	SortableItem,
} from '~/settings-payments/components/sortable';
import './bank-account-list.scss';

/**
 * Generates a random string ID for new bank accounts.
 *
 * @return {string} A unique identifier string.
 */
function generateId() {
	return Math.random().toString( 36 ).substring( 2, 10 );
}

/**
 * Props for BankAccountsList component.
 *
 */
interface Props {
	initialAccounts: BankAccount[];
	onChange: ( accounts: BankAccount[] ) => void;
	updateOrdering: ( accounts: BankAccount[] ) => void;
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
	initialAccounts,
	onChange,
	updateOrdering,
	defaultCountry,
}: Props ) => {
	const [ accounts, setAccounts ] = useState( initialAccounts );
	const [ selectedAccount, setSelectedAccount ] =
		useState< BankAccount | null >( null );
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ accountToDelete, setAccountToDelete ] =
		useState< BankAccount | null >( null );

	/**
	 * Opens the bank account modal for adding or editing an account.
	 *
	 * @param {BankAccount | null} account The account to edit, or null to add a new one.
	 */
	const openModal = ( account: BankAccount | null = null ) => {
		setSelectedAccount( account );
		setIsModalOpen( true );
	};

	/**
	 * Handles saving of a bank account, either updating an existing one or adding a new one.
	 *
	 * @param {BankAccount} updated The updated or new bank account.
	 */
	const handleSave = ( updated: BankAccount ) => {
		const newAccounts = accounts.some( ( acc ) => acc.id === updated.id )
			? accounts.map( ( acc ) =>
					acc.id === updated.id ? updated : acc
			  )
			: [ ...accounts, { ...updated, id: generateId() } ];
		setAccounts( newAccounts );
		onChange( newAccounts );
		setIsModalOpen( false );
	};

	/**
	 * Confirms and deletes the selected bank account.
	 */
	const confirmDelete = () => {
		if ( ! accountToDelete ) return;
		const newAccounts = accounts.filter(
			( acc ) => acc.id !== accountToDelete.id
		);
		setAccounts( newAccounts );
		onChange( newAccounts );
		setAccountToDelete( null );
	};

	/**
	 * Updates the ordering of bank accounts after drag-and-drop sorting.
	 *
	 * @param {BankAccount[]} newAccounts The reordered list of bank accounts.
	 */
	const handleUpdateOrdering = ( newAccounts: BankAccount[] ) => {
		setAccounts( newAccounts );
		updateOrdering( newAccounts );
	};

	return (
		<>
			<SortableContainer< BankAccount >
				items={ accounts }
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
				{ accounts.map( ( account, index ) => (
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
									renderContent={ () => (
										<MenuGroup>
											<MenuItem
												role={ 'menuitem' }
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
													setAccountToDelete(
														account
													)
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
							</div>
						</div>
					</SortableItem>
				) ) }
				<li className="bank-accounts__list-item action">
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
