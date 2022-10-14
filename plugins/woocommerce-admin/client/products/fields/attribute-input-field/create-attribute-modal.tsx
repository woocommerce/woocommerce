/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	Modal,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import { Form, FormContext, FormErrors } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	QueryProductAttribute,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './create-attribute-modal.scss';
import {
	getCheckboxProps,
	getTextControlProps,
} from '~/products/sections/utils';

type CreateAttributeModalProps = {
	initialAttributeName: string;
	onCancel: () => void;
	onCreated: ( newAttribute: QueryProductAttribute ) => void;
};

const ATTRIBUTE_SORT_ORDER_OPTIONS = [
	{
		value: 'menu_order',
		label: __( 'Custom ordering', 'woocommerce' ),
	},
	{
		value: 'name',
		label: __( 'name', 'woocommerce' ),
	},
	{
		value: 'name_num',
		label: __( 'Name (numeric)', 'woocommerce' ),
	},
	{
		value: 'id',
		label: __( 'Term ID', 'woocommerce' ),
	},
];

export const CreateAttributeModal: React.FC< CreateAttributeModalProps > = ( {
	initialAttributeName,
	onCancel,
	onCreated,
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isCreating, setIsCreating ] = useState( false );
	const { createProductAttribute, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME );

	const onAdd = async ( attribute: Partial< QueryProductAttribute > ) => {
		recordEvent( 'product_attribute_add', {
			new_product_page: true,
		} );
		setIsCreating( true );
		try {
			const newAttribute: QueryProductAttribute =
				await createProductAttribute( attribute );
			recordEvent( 'product_attribute_add_success', {
				new_product_page: true,
			} );
			invalidateResolutionForStoreSelector( 'getProductAttributes' );
			setIsCreating( false );
			onCreated( newAttribute );
		} catch ( e ) {
			recordEvent( 'product_attribute_add_failed', {
				new_product_page: true,
			} );
			createNotice(
				'error',
				__( 'Failed to create attribute.', 'woocommerce' )
			);
			setIsCreating( false );
			onCancel();
		}
	};

	function validateForm(
		values: Partial< QueryProductAttribute >
	): FormErrors< QueryProductAttribute > {
		const errors: FormErrors< QueryProductAttribute > = {};

		if ( ! values.name?.length ) {
			errors.name = __(
				'The attribute name is required.',
				'woocommerce'
			);
		}

		return errors;
	}

	return (
		<Modal
			title={ __( 'Create attribute', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-create-attribute-modal"
		>
			<Form< Partial< QueryProductAttribute > >
				initialValues={ {
					name: initialAttributeName,
					slug: cleanForSlug( initialAttributeName ),
				} }
				validate={ validateForm }
				errors={ {} }
				onSubmit={ onAdd }
			>
				{ ( {
					getInputProps,
					handleSubmit,
					isValidForm,
					setValue,
					values,
				}: FormContext< QueryProductAttribute > ) => {
					const nameInputProps = getInputProps< string >( 'name' );
					return (
						<>
							<TextControl
								label={ __( 'Name', 'woocommerce' ) }
								{ ...nameInputProps }
								onBlur={ () => {
									nameInputProps.onBlur();
									setValue(
										'slug',
										cleanForSlug( values.name )
									);
								} }
							/>
							<TextControl
								label={ __( 'Slug', 'woocommerce' ) }
								{ ...getInputProps( 'slug' ) }
							/>
							<CheckboxControl
								label={ __(
									'Enable Archives?',
									'woocommerce'
								) }
								{ ...getCheckboxProps( {
									...getInputProps( 'has_archives' ),
								} ) }
							/>
							<SelectControl
								{ ...getTextControlProps(
									getInputProps( 'order_by' )
								) }
								label={ __(
									'Default sort order',
									'woocommerce'
								) }
								options={ ATTRIBUTE_SORT_ORDER_OPTIONS }
							/>
							<div className="woocommerce-create-attribute-modal__buttons">
								<Button
									isSecondary
									label={ __( 'Cancel', 'woocommerce' ) }
									onClick={ () => onCancel() }
								>
									{ __( 'Cancel', 'woocommerce' ) }
								</Button>
								<Button
									isPrimary
									isBusy={ isCreating }
									label={ __(
										'Add attribute',
										'woocommerce'
									) }
									disabled={ ! isValidForm || isCreating }
									onClick={ handleSubmit }
								>
									{ __( 'Add', 'woocommerce' ) }
								</Button>
							</div>
						</>
					);
				} }
			</Form>
		</Modal>
	);
};
