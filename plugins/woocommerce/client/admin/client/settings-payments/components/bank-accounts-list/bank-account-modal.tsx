/**
 * External dependencies
 */
import { Modal, TextControl, Button } from '@wordpress/components';
import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BankAccount } from './types';
import { getDefaultRoutingField } from './utils';
import { validateRequiredField, validateNumericField } from './validation';
import './bank-account-modal.scss';

/**
 * Props for the BankAccountModal component.
 *
 * @property {BankAccount | null}             account        - The bank account to edit, or null to add a new account.
 * @property {() => void}                     onClose        - Callback invoked when the modal should be closed.
 * @property {(account: BankAccount) => void} onSave         - Callback invoked when the bank account is saved.
 * @property {string}                         defaultCountry - The default country used to determine the routing field.
 */
interface Props {
	account: BankAccount | null;
	onClose: () => void;
	onSave: ( account: BankAccount ) => void;
	defaultCountry: string;
}

/**
 * BankAccountModal component renders a modal dialog for adding or editing a bank account.
 * It manages form state, validation, and invokes callbacks to save or close the modal.
 *
 * @param {Props} props - Component props.
 * @return {Element} The rendered modal component.
 */
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

	const [ errors, setErrors ] = useState<
		Partial< Record< keyof BankAccount, string > >
	>( {} );

	/**
	 * Validates the form fields and sets error messages accordingly.
	 *
	 * @return {boolean} True if the form is valid, false otherwise.
	 */
	const validate = () => {
		const newErrors: Partial< Record< keyof BankAccount, string > > = {};

		newErrors.account_name = validateRequiredField( formData.account_name );
		newErrors.account_number =
			validateRequiredField( formData.account_number ) ||
			validateNumericField( formData.account_number );

		if ( routingField === 'routing_number' ) {
			newErrors.routing_number = validateRequiredField(
				formData.routing_number
			);
		}

		if ( routingField === 'sort_code' ) {
			newErrors.sort_code = validateRequiredField( formData.sort_code );
		}

		if ( routingField === 'iban' ) {
			newErrors.iban = validateRequiredField( formData.iban );
		}

		newErrors.bic = validateRequiredField( formData.bic );

		const filteredErrors = Object.fromEntries(
			Object.entries( newErrors ).filter( ( [ , v ] ) => v )
		);
		setErrors( filteredErrors );

		return Object.keys( filteredErrors ).length === 0;
	};

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

	/**
	 * Updates a specific field in the form data state.
	 *
	 * @param {keyof BankAccount} field - The field name to update.
	 * @param {string}            value - The new value for the field.
	 */
	const updateField = ( field: keyof BankAccount, value: string ) => {
		setFormData( ( prev ) => ( { ...prev, [ field ]: value } ) );
	};

	return (
		<Modal
			className="bank-account-modal"
			title={
				account
					? __( 'Edit bank account', 'woocommerce' )
					: __( 'Add a bank account', 'woocommerce' )
			}
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
		>
			<p className={ 'bank-account-modal__description' }>
				{ account
					? __( 'Edit your bank account details.', 'woocommerce' )
					: __( 'Add your bank account details.', 'woocommerce' ) }
			</p>

			<TextControl
				className={ 'bank-account-modal__field' }
				label={ __( 'Account Name *', 'woocommerce' ) }
				required
				value={ formData.account_name }
				onChange={ ( value ) => updateField( 'account_name', value ) }
				help={
					errors.account_name ? (
						<span className="bank-account-modal__error">
							{ errors.account_name }
						</span>
					) : undefined
				}
			/>

			<TextControl
				className={ 'bank-account-modal__field' }
				label={ __( 'Account Number *', 'woocommerce' ) }
				required
				value={ formData.account_number }
				onChange={ ( value ) => updateField( 'account_number', value ) }
				help={
					errors.account_number ? (
						<span className="bank-account-modal__error">
							{ errors.account_number }
						</span>
					) : undefined
				}
			/>

			<TextControl
				className={ 'bank-account-modal__field' }
				label={ __( 'Bank Name', 'woocommerce' ) }
				value={ formData.bank_name }
				onChange={ ( value ) => updateField( 'bank_name', value ) }
			/>

			{ routingField === 'routing_number' && (
				<TextControl
					className={ 'bank-account-modal__field' }
					label={ __( 'Routing Number', 'woocommerce' ) }
					required
					value={ formData.routing_number }
					onChange={ ( value ) =>
						updateField( 'routing_number', value )
					}
					help={
						errors.routing_number ? (
							<span className="bank-account-modal__error">
								{ errors.routing_number }
							</span>
						) : undefined
					}
				/>
			) }

			{ routingField === 'sort_code' && (
				<TextControl
					className={ 'bank-account-modal__field' }
					label={ __( 'BSB', 'woocommerce' ) }
					required
					value={ formData.sort_code }
					onChange={ ( value ) => updateField( 'sort_code', value ) }
					help={
						errors.sort_code ? (
							<span className="bank-account-modal__error">
								{ errors.sort_code }
							</span>
						) : undefined
					}
				/>
			) }

			{ routingField === 'iban' && (
				<TextControl
					className={ 'bank-account-modal__field' }
					label={ __( 'IBAN', 'woocommerce' ) }
					required
					value={ formData.iban }
					onChange={ ( value ) => updateField( 'iban', value ) }
					help={
						errors.iban ? (
							<span className="bank-account-modal__error">
								{ errors.iban }
							</span>
						) : undefined
					}
				/>
			) }

			<TextControl
				className={ 'bank-account-modal__field' }
				label={ __( 'BIC / SWIFT', 'woocommerce' ) }
				value={ formData.bic }
				onChange={ ( value ) => updateField( 'bic', value ) }
				help={
					errors.bic ? (
						<span className="bank-account-modal__error">
							{ errors.bic }
						</span>
					) : undefined
				}
			/>

			<div className={ 'bank-account-modal__actions' }>
				<Button variant={ 'tertiary' } onClick={ onClose }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					className={ 'bank-account-modal__save' }
					variant={ 'primary' }
					onClick={ () => {
						if ( validate() ) {
							onSave( formData );
						}
					} }
				>
					{ __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
};
