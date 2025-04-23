/**
 * External dependencies
 */
import { Button, MenuGroup, MenuItem } from '@wordpress/components';
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

function generateId() {
	return Math.random().toString( 36 ).substring( 2, 10 );
}
interface Props {
	accounts: BankAccount[];
	onChange: ( accounts: BankAccount[] ) => void;
	updateOrdering: ( accounts: BankAccount[] ) => void;
	defaultCountry: string;
}

export const BankAccountsList = ( {
	accounts,
	onChange,
	updateOrdering,
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
		<>
			<SortableContainer< BankAccount >
				items={ accounts }
				className={ 'bank-accounts__list' }
				setItems={ updateOrdering }
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
		</>
	);
};
