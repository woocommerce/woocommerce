/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Modal,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { useState, createElement, Fragment } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import { Form, FormErrors } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	ProductAttributeTerm,
	experimentalProductAttributeTermsStore,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';

type CreateAttributeTermModalProps = {
	initialAttributeTermName: string;
	attributeId: number;
	onCancel?: () => void;
	onCreated?: ( newAttribute: ProductAttributeTerm ) => void;
};

export const CreateAttributeTermModal: React.FC<
	CreateAttributeTermModalProps
> = ( {
	initialAttributeTermName,
	attributeId,
	onCancel = () => {},
	onCreated = () => {},
} ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isCreating, setIsCreating ] = useState( false );
	const { createProductAttributeTerm } = useDispatch(
		experimentalProductAttributeTermsStore
	);

	const onAdd = async ( attribute: Partial< ProductAttributeTerm > ) => {
		recordEvent( 'product_attribute_term_add', {
			source: TRACKS_SOURCE,
		} );
		setIsCreating( true );
		try {
			const newAttribute = await createProductAttributeTerm( {
				...attribute,
				attribute_id: attributeId,
			} );
			recordEvent( 'product_attribute_term_add_success', {
				source: TRACKS_SOURCE,
			} );
			setIsCreating( false );
			onCreated( newAttribute );
		} catch ( e ) {
			recordEvent( 'product_attribute_term_add_failed', {
				source: TRACKS_SOURCE,
			} );
			createNotice(
				'error',
				__( 'Failed to create attribute term.', 'woocommerce' )
			);
			setIsCreating( false );
			onCancel();
		}
	};

	function validateForm(
		values: Partial< ProductAttributeTerm >
	): FormErrors< ProductAttributeTerm > {
		const errors: FormErrors< ProductAttributeTerm > = {};

		if ( ! values.name?.length ) {
			errors.name = __(
				'The attribute term name is required.',
				'woocommerce'
			);
		}

		return errors;
	}

	return (
		<Modal
			title={ __( 'Create attribute', 'woocommerce' ) }
			onRequestClose={ ( event ) => {
				event?.stopPropagation();
				onCancel();
			} }
			className="woocommerce-create-attribute-term-modal"
		>
			<Form<
				Pick< ProductAttributeTerm, 'name' | 'slug' | 'description' >
			>
				initialValues={ {
					name: initialAttributeTermName,
					slug: cleanForSlug( initialAttributeTermName ),
					description: '',
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
				} ) => {
					const nameInputProps = getInputProps( 'name' );
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
								help={ __(
									'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.',
									'woocommerce'
								) }
							/>
							<TextareaControl
								label={ __( 'Description', 'woocommerce' ) }
								{ ...getInputProps( 'description' ) }
							/>
							<div className="woocommerce-create-attribute-term-modal__buttons">
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
