/**
 * External dependencies
 */
import { BaseControl, Button, Modal, TextControl } from '@wordpress/components';
import {
	useState,
	createElement,
	createInterpolateElement,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Form, FormErrors, useFormContext } from '@woocommerce/components';
import { ProductShippingClass } from '@woocommerce/data';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

export type ShippingClassFormProps = {
	onAdd: () => Promise< ProductShippingClass >;
	onCancel: () => void;
};

function ShippingClassForm( { onAdd, onCancel }: ShippingClassFormProps ) {
	const { errors, getInputProps, isValidForm } =
		useFormContext< ProductShippingClass >();
	const [ isLoading, setIsLoading ] = useState( false );

	function handleAdd() {
		setIsLoading( true );
		onAdd()
			.then( () => {
				setIsLoading( false );
				onCancel();
			} )
			.catch( () => {
				setIsLoading( false );
			} );
	}

	// State to control the automatic slug generation.
	const [ isRequestingSlug, setIsRequestingSlug ] = useState( false );

	// Get the shipping class name value.
	const shippingNameInputValue = String( getInputProps( 'name' ).value );

	const [ prevNameValue, setPrevNameValue ] = useState(
		shippingNameInputValue
	);

	/**
	 * Pull the slug suggestion from the server,
	 * and update the slug input field.
	 */
	async function pullAndupdateSlugInputField() {
		setIsRequestingSlug( true );

		// Avoid making the request if the name has not changed.
		if ( prevNameValue === shippingNameInputValue ) {
			return;
		}

		setPrevNameValue( shippingNameInputValue );

		const url = `/wc/v3/products/shipping_classes/slug-suggestion`;
		const slug: string = await apiFetch( {
			path: addQueryArgs( url, { name: shippingNameInputValue } ),
			method: 'GET',
		} );

		setIsRequestingSlug( false );

		getInputProps( 'slug' ).onChange( slug );
	}

	const isGenerateButtonDisabled =
		isRequestingSlug ||
		! shippingNameInputValue?.length ||
		prevNameValue === shippingNameInputValue;

	/**
	 * Get a slug suggestion based on the shipping class name.
	 * This function is called when the name field is blurred.
	 */
	function getSlugSuggestion() {
		if ( ! isRequestingSlug ) {
			return;
		}

		pullAndupdateSlugInputField();
	}

	return (
		<div className="woocommerce-add-new-shipping-class-modal__wrapper">
			<TextControl
				{ ...getInputProps( 'name' ) }
				placeholder={ __( 'e.g. Fragile products', 'woocommerce' ) }
				label={ createInterpolateElement(
					__( 'Name <required />', 'woocommerce' ),
					{
						required: (
							<span className="woocommerce-add-new-shipping-class-modal__optional-input">
								{ __( '(required)', 'woocommerce' ) }
							</span>
						),
					}
				) }
				onBlur={ getSlugSuggestion }
			/>

			<div className="woocommerce-add-new-shipping-class-modal__slug-section">
				<TextControl
					{ ...getInputProps( 'slug' ) }
					className="woocommerce-add-new-shipping-class-modal__slug-input"
					label={ __( 'Custom', 'woocommerce' ) }
					onChange={ ( value ) => {
						setPrevNameValue( '' ); // clean the previous name value.
						getInputProps( 'slug' ).onChange( value );
					} }
					disabled={ isRequestingSlug }
				/>

				<BaseControl
					id="automatic-slug"
					label={ __( 'Automatic', 'woocommerce' ) }
					className="woocommerce-add-new-shipping-class-modal__slug-button"
				>
					<Button
						disabled={ isGenerateButtonDisabled }
						variant="secondary"
						onClick={ pullAndupdateSlugInputField }
						isBusy={ isRequestingSlug }
					>
						{ __( 'Generate', 'woocommerce' ) }
					</Button>
				</BaseControl>
			</div>

			<TextControl
				{ ...getInputProps( 'description' ) }
				label={ __( 'Description', 'woocommerce' ) }
				help={
					errors?.description ??
					__(
						'Describe how you and other store administrators can use this shipping class.',
						'woocommerce'
					)
				}
			/>

			<div className="woocommerce-add-new-shipping-class-modal__buttons">
				<Button isSecondary onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					isPrimary
					isBusy={ isLoading }
					disabled={ ! isValidForm || isLoading }
					onClick={ handleAdd }
				>
					{ __( 'Add', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
}

function validateForm(
	values: Partial< ProductShippingClass >
): FormErrors< ProductShippingClass > {
	const errors: FormErrors< ProductShippingClass > = {};

	if ( ! values.name?.length ) {
		errors.name = __(
			'The shipping class name is required.',
			'woocommerce'
		);
	}

	return errors;
}

export type AddNewShippingClassModalProps = {
	shippingClass?: Partial< ProductShippingClass >;
	onAdd: (
		shippingClass: Partial< ProductShippingClass >
	) => Promise< ProductShippingClass >;
	onCancel: () => void;
};

const INITIAL_VALUES = { name: '', slug: '', description: '' };

export function AddNewShippingClassModal( {
	shippingClass,
	onAdd,
	onCancel,
}: AddNewShippingClassModalProps ) {
	function handleSubmit( values: Partial< ProductShippingClass > ) {
		return onAdd(
			Object.entries( values ).reduce( function removeEmptyValue(
				current,
				[ name, value ]
			) {
				return {
					...current,
					[ name ]: value === '' ? undefined : value,
				};
			},
			{} )
		);
	}

	return (
		<Modal
			title={ __( 'New shipping class', 'woocommerce' ) }
			className="woocommerce-add-new-shipping-class-modal"
			onRequestClose={ onCancel }
		>
			<Form< Partial< ProductShippingClass > >
				initialValues={ shippingClass ?? INITIAL_VALUES }
				validate={ validateForm }
				errors={ {} }
				onSubmit={ handleSubmit }
			>
				{ ( childrenProps: {
					handleSubmit: () => Promise< ProductShippingClass >;
				} ) => (
					<ShippingClassForm
						onAdd={ childrenProps.handleSubmit }
						onCancel={ onCancel }
					/>
				) }
			</Form>
		</Modal>
	);
}
