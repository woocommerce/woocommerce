/**
 * External dependencies
 */
import {
	Modal,
	TextControl,
	SelectControl,
	Button,
} from '@wordpress/components';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

interface BankAccountModalProps {
	account: {
		country: string;
		name: string;
		number: string;
		bank: string;
		routing: string;
		bic: string;
	};
	onClose: () => void;
}

export const BankAccountModal = ( {
	account,
	onClose,
}: BankAccountModalProps ) => {
	const [ formData, setFormData ] = useState( {
		country: account?.country || '',
		name: account?.name || '',
		number: account?.number || '',
		bank: account?.bank || '',
		routing: account?.routing || '',
		bic: account?.bic || '',
	} );

	const updateField = ( field, value ) => {
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
			<SelectControl
				label={ __( 'Country', 'woocommerce' ) }
				required
				value={ formData.country }
				options={ [
					{ label: __( 'Please select', 'woocommerce' ), value: '' },
					{ label: 'United States', value: 'US' },
					{ label: 'Germany', value: 'DE' },
					{ label: 'Australia', value: 'AU' },
				] }
				onChange={ ( value ) => updateField( 'country', value ) }
			/>

			<TextControl
				label={ __( 'Account Name', 'woocommerce' ) }
				required
				value={ formData.name }
				onChange={ ( value ) => updateField( 'name', value ) }
			/>

			<TextControl
				label={ __( 'Account Number', 'woocommerce' ) }
				required
				value={ formData.number }
				onChange={ ( value ) => updateField( 'number', value ) }
			/>

			<TextControl
				label={ __( 'Bank Name', 'woocommerce' ) }
				value={ formData.bank }
				onChange={ ( value ) => updateField( 'bank', value ) }
			/>

			<TextControl
				label={ __( 'Routing Number', 'woocommerce' ) }
				required={ !! formData.country && formData.country === 'US' }
				value={ formData.routing }
				onChange={ ( value ) => updateField( 'routing', value ) }
			/>

			<TextControl
				label={ __( 'BIC/SWIFT', 'woocommerce' ) }
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
				<Button isSecondary onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					isPrimary
					style={ { marginLeft: '8px' } }
					onClick={ () => {
						/* save logic */
					} }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default BankAccountModal;
