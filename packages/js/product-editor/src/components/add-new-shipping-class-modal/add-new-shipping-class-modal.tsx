/**
 * External dependencies
 */
import {
	Button,
	CheckboxControl,
	Modal,
	TextControl,
} from '@wordpress/components';
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

// Store a refreshed value of the shipping class name.
let shippingName = '';

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
	const [ automaticSlug, setAutomaticSlug ] = useState( true );

	// Get the shipping class name value.
	const shippingNameValue = getInputProps( 'name' ).value;

	/**
	 * Get a slug suggestion based on the shipping class name.
	 * This function is called when the name field is blurred.
	 */
	const getSlugSuggestion = async () => {
		if ( ! automaticSlug ) {
			return;
		}

		if ( shippingName === shippingNameValue ) {
			return;
		}

		shippingName = String( shippingNameValue );

		const url = `/wc/v3/products/shipping_classes/slug-suggestion`;
		const slug: string = await apiFetch( {
			path: addQueryArgs( url, { name: shippingName } ),
			method: 'GET',
		} );

		getInputProps( 'slug' ).onChange( slug );
	};

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

			<CheckboxControl
				label={ __(
					'Generate slug automatically based on the name',
					'woocommerce'
				) }
				checked={ automaticSlug }
				onChange={ setAutomaticSlug }
				onBlur={ getSlugSuggestion }
			/>

			<TextControl
				{ ...getInputProps( 'slug' ) }
				label={ __( 'Custom slug', 'woocommerce' ) }
				disabled={ automaticSlug }
			/>

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
