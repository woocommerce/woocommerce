/**
 * External dependencies
 */
import interpolateComponents from '@automattic/interpolate-components';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Form, FormErrors, useFormContext } from '@woocommerce/components';
import { ProductShippingClass } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getTextControlProps } from '../../sections/utils';
import './add-new-shipping-class-modal.scss';

export type ShippingClassFormProps = {
	onAdd: () => Promise< ProductShippingClass >;
	onCancel: () => void;
};

function ShippingClassForm( { onAdd, onCancel }: ShippingClassFormProps ) {
	const { getInputProps, isValidForm } =
		useFormContext< ProductShippingClass >();
	const [ isLoading, setIsLoading ] = useState( false );

	const inputNameProps = getTextControlProps( getInputProps( 'name' ) );
	const inputSlugProps = getTextControlProps( getInputProps( 'slug' ) );
	const inputDescriptionProps = getTextControlProps(
		getInputProps( 'description' )
	);

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

	return (
		<div className="woocommerce-add-new-shipping-class-modal__wrapper">
			<TextControl
				{ ...inputNameProps }
				label={ __( 'Name', 'woocommerce' ) }
			/>
			<TextControl
				{ ...inputSlugProps }
				label={ interpolateComponents( {
					mixedString: __(
						'Slug {{span}}(optional){{/span}}',
						'woocommerce'
					),
					components: {
						span: (
							<span className="woocommerce-add-new-shipping-class-modal__optional-input" />
						),
					},
				} ) }
			/>
			<TextControl
				{ ...inputDescriptionProps }
				label={ interpolateComponents( {
					mixedString: __(
						'Description {{span}}(optional){{/span}}',
						'woocommerce'
					),
					components: {
						span: (
							<span className="woocommerce-add-new-shipping-class-modal__optional-input" />
						),
					},
				} ) }
				help={
					inputDescriptionProps?.help ??
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

const INITIAL_VALUES = {
	name: __( 'New shipping class', 'woocommerce' ),
	slug: __( 'new-shipping-class', 'woocommerce' ),
};

export function AddNewShippingClassModal( {
	shippingClass,
	onAdd,
	onCancel,
}: AddNewShippingClassModalProps ) {
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
				onSubmit={ onAdd }
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
