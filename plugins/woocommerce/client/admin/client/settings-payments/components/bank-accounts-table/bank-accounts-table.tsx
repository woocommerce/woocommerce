import { Button, Card, Icon, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { EllipsisMenu } from '@woocommerce/components';
import { useState } from 'react';
import BankAccountModal from './bank-account-modal';

export default function BankAccountsTable() {
	const [ selectedAccount, setSelectedAccount ] = useState( null );
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	const accounts = [
		{
			id: 1,
			name: 'Red Potato Shop Inc',
			number: '123456789',
			bank: 'Bank of America',
		},
		{
			id: 2,
			name: 'Sarah Lee',
			number: '987654321',
			bank: 'Commonwealth Bank',
		},
		{
			id: 3,
			name: 'Max Müller',
			number: 'DE44500105175407324931',
			bank: 'Deutsche Bank',
		},
	];

	const openModal = ( account = null ) => {
		setSelectedAccount( account );
		setIsModalOpen( true );
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
							<td>{ account.name }</td>
							<td>{ account.number }</td>
							<td>{ account.bank }</td>
							<td>
								<EllipsisMenu
									label={ __( 'Options', 'woocommerce' ) }
									icon={ moreVertical }
									popoverProps={ {
										position: 'bottom right',
									} }
								>
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
											onClick={ () => {
												/* delete logic */
											} }
										>
											{ __( 'Delete', 'woocommerce' ) }
										</MenuItem>
									</MenuGroup>
								</EllipsisMenu>
							</td>
						</tr>
					) ) }
					<tr>
						<td colSpan={ 4 }>
							<Button isSecondary onClick={ () => openModal() }>
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
				/>
			) }
		</Card>
	);
}
