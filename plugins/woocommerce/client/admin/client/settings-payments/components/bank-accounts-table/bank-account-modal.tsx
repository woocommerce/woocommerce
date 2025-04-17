/**
 * External dependencies
 */
import { Modal, TextControl, Button } from '@wordpress/components';
import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BankAccount } from './bank-accounts-table';
import { getDefaultRoutingField } from './utils';

interface Props {
	account: BankAccount | null;
	onClose: () => void;
	onSave: ( account: BankAccount ) => void;
	defaultCountry: string;
}

export const BankAccountModal = ( {
	account,
	onClose,
	onSave,
	defaultCountry,
}: Props ) => {
	const [ formData, setFormData ] = useState< BankAccount >(
		account || {
			id: '',
			account_name: '',
			account_number: '',
			bank_name: '',
			routing_number: '',
			sort_code: '',
			iban: '',
			bic: '',
		}
	);
	const [ routingField, setRoutingField ] = useState<
		'routing_number' | 'sort_code' | 'iban'
	>( 'iban' );

	useEffect( () => {
		if ( account ) {
			if ( account.routing_number ) setRoutingField( 'routing_number' );
			else if ( account.sort_code ) setRoutingField( 'sort_code' );
			else if ( account.iban ) setRoutingField( 'iban' );
			else setRoutingField( getDefaultRoutingField( defaultCountry ) );
		} else {
			setRoutingField( getDefaultRoutingField( defaultCountry ) );
		}
	}, [ account, defaultCountry ] );

	const updateField = ( field: keyof BankAccount, value: string ) => {
		setFormData( ( prev ) => ( { ...prev, [ field ]: value } ) );
	};

	return (
		<Modal
			title={
				account
					? __( 'Edit bank account', 'woocommerce' )
					: __( 'Add a bank account', 'woocommerce' )
			}
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
		>
			<p>{ __( 'Add your bank account details.', 'woocommerce' ) }</p>

			<TextControl
				label={ __( 'Account Name', 'woocommerce' ) }
				required
				value={ formData.account_name }
				onChange={ ( value ) => updateField( 'account_name', value ) }
			/>

			<TextControl
				label={ __( 'Account Number', 'woocommerce' ) }
				required
				value={ formData.account_number }
				onChange={ ( value ) => updateField( 'account_number', value ) }
			/>

			<TextControl
				label={ __( 'Bank Name', 'woocommerce' ) }
				value={ formData.bank_name }
				onChange={ ( value ) => updateField( 'bank_name', value ) }
			/>

			{ routingField === 'routing_number' && (
				<TextControl
					label={ __( 'Routing Number', 'woocommerce' ) }
					required
					value={ formData.routing_number }
					onChange={ ( value ) =>
						updateField( 'routing_number', value )
					}
				/>
			) }

			{ routingField === 'sort_code' && (
				<TextControl
					label={ __( 'BSB', 'woocommerce' ) }
					required
					value={ formData.sort_code }
					onChange={ ( value ) => updateField( 'sort_code', value ) }
				/>
			) }

			{ routingField === 'iban' && (
				<TextControl
					label={ __( 'IBAN', 'woocommerce' ) }
					required
					value={ formData.iban }
					onChange={ ( value ) => updateField( 'iban', value ) }
				/>
			) }

			<TextControl
				label={ __( 'BIC / SWIFT', 'woocommerce' ) }
				value={ formData.bic }
				onChange={ ( value ) => updateField( 'bic', value ) }
			/>

			<div
				style={ {
					display: 'flex',
					justifyContent: 'flex-end',
					marginTop: '16px',
				} }
			>
				<Button variant={ 'secondary' } onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant={ 'primary' }
					style={ { marginLeft: '8px' } }
					onClick={ () => onSave( formData ) }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
