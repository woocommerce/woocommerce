/**
 * External dependencies
 */
import {
	useState,
	createElement,
	createInterpolateElement,
	useRef,
	useEffect,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Form, FormErrors, useFormContext } from '@woocommerce/components';
import { ProductShippingClass } from '@woocommerce/data';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Modal,
	TextControl,
	__experimentalInputControl as InputControl,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
} from '@wordpress/components';

export type ShippingClassFormProps = {
	onAdd: () => Promise< void >;
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

	// Get the reference of the name field
	const nameRef = useRef< HTMLInputElement | null >( null );

	// Focus in the name field when the component is mounted.
	useEffect( () => {
		nameRef.current?.focus();
	}, [] );

	/**
	 * Pull the slug suggestion from the server,
	 * and update the slug input field.
	 */
	async function pullAndUpdateSlugInputField() {
		setIsRequestingSlug( true );

		// Avoid making the request if the name has not changed.
		if ( prevNameValue === shippingNameInputValue ) {
			return;
		}

		setIsRequestingSlug( true );

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

		pullAndUpdateSlugInputField();
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
				ref={ nameRef }
			/>

			<InputControl
				{ ...getInputProps( 'slug' ) }
				label={ __( 'Slug', 'woocommerce' ) }
				onChange={ ( value ) => {
					setPrevNameValue( '' ); // clean the previous name value.
					getInputProps( 'slug' ).onChange( value ?? '' );
				} }
				disabled={ isRequestingSlug }
				help={ __(
					'Set a custom slug or generate it by clicking the button.',
					'woocommerce'
				) }
				prefix={
					<InputControlPrefixWrapper>
						<Button
							disabled={ isGenerateButtonDisabled }
							variant="secondary"
							onClick={ pullAndUpdateSlugInputField }
							isBusy={ isRequestingSlug }
							isSmall
						>
							{ __( 'Generate', 'woocommerce' ) }
						</Button>
					</InputControlPrefixWrapper>
				}
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
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
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
				{ ( childrenProps ) => (
					<ShippingClassForm
						onAdd={ childrenProps.handleSubmit }
						onCancel={ onCancel }
					/>
				) }
			</Form>
		</Modal>
	);
}
