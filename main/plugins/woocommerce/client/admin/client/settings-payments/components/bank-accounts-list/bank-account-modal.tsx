/**
 * External dependencies
 */
import {
	Modal,
	TextControl,
	SelectControl,
	Button,
} from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BankAccount } from './types';
import {
	formatSortCode,
	getSortCodeLabel,
	shouldDisplaySortCode,
} from './utils';
import { validateRequiredField } from './validation';
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
	const countries = window.wcSettings.countries;
	const [ formData, setFormData ] = useState< BankAccount >(
		account || {
			account_name: '',
			account_number: '',
			bank_name: '',
			sort_code: '',
			iban: '',
			bic: '',
			country_code: defaultCountry,
		}
	);
	const [ selectedCountry, setSelectedCountry ] = useState(
		account?.country_code || defaultCountry
	);
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

		if ( shouldDisplaySortCode( selectedCountry ) ) {
			newErrors.sort_code = validateRequiredField( formData.sort_code );
		}

		const filteredErrors = Object.fromEntries(
			Object.entries( newErrors ).filter( ( [ , v ] ) => v )
		);
		setErrors( filteredErrors );

		return Object.keys( filteredErrors ).length === 0;
	};

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
			<div className={ 'bank-account-modal__content' }>
				<p className={ 'bank-account-modal__description' }>
					{ account
						? __( 'Edit your bank account details.', 'woocommerce' )
						: __(
								'Add your bank account details.',
								'woocommerce'
						  ) }
				</p>

				<SelectControl
					className="bank-account-modal__field is-required"
					label={ __( 'Country', 'woocommerce' ) }
					required
					value={ selectedCountry }
					options={ Object.entries( countries ).map(
						( [ code, name ] ) => ( {
							label: decodeEntities( name ),
							value: code,
						} )
					) }
					onChange={ ( value ) => {
						setSelectedCountry( value );
						updateField( 'country_code', value );
						// Clear the because sort codes have different formats in different countries.
						updateField( 'sort_code', '' );
					} }
				/>

				<TextControl
					className={ 'bank-account-modal__field is-required' }
					label={ __( 'Account Name', 'woocommerce' ) }
					required
					value={ formData.account_name }
					onChange={ ( value ) =>
						updateField( 'account_name', value )
					}
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
					label={ __( 'Bank Name', 'woocommerce' ) }
					value={ formData.bank_name }
					onChange={ ( value ) => updateField( 'bank_name', value ) }
				/>

				<TextControl
					className={ 'bank-account-modal__field' }
					label={ __( 'Account Number', 'woocommerce' ) }
					value={ formData.account_number }
					onChange={ ( value ) =>
						updateField( 'account_number', value )
					}
					help={
						errors.account_number ? (
							<span className="bank-account-modal__error">
								{ errors.account_number }
							</span>
						) : undefined
					}
				/>

				{ shouldDisplaySortCode( selectedCountry ) && (
					<TextControl
						className={ 'bank-account-modal__field is-required' }
						label={ getSortCodeLabel( selectedCountry ) }
						required
						value={ formatSortCode(
							formData.sort_code || '',
							selectedCountry
						) }
						onChange={ ( value ) => {
							// Strip all non-digit characters to get the raw value
							if (
								selectedCountry === 'GB' ||
								selectedCountry === 'IE'
							) {
								value = value
									.replace( /\D/g, '' )
									.substring( 0, 6 );
							}

							// Store or pass the raw value:
							updateField( 'sort_code', value );
						} }
						help={
							errors.sort_code ? (
								<span className="bank-account-modal__error">
									{ errors.sort_code }
								</span>
							) : undefined
						}
					/>
				) }

				<TextControl
					className={ 'bank-account-modal__field' }
					label={ __( 'IBAN', 'woocommerce' ) }
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
			</div>

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
