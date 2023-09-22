/**
 * External dependencies
 */
import { createElement, createInterpolateElement } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { __, sprintf } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
import classNames from 'classnames';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useValidation } from '../../contexts/validation-context';
import useMetaEntityProp from '../../hooks/use-meta-entity-prop';

interface TextBlockAttributes extends BlockAttributes {
	property: string;
	label?: string;
	placeholder?: string;
	required: boolean;
	validationRegex?: string;
	validationErrorMessage?: string;
	isMeta: boolean;
	minLength?: number;
	maxLength?: number;
}

export function Edit( { attributes }: { attributes: TextBlockAttributes } ) {
	const blockProps = useBlockProps();
	const {
		property,
		label,
		placeholder,
		required,
		validationRegex,
		validationErrorMessage,
		isMeta,
		minLength,
		maxLength,
	} = attributes;
	const [ value, setValue ] = useMetaEntityProp( isMeta, property );
	const nameControlId = useInstanceId( BaseControl, property ) as string;

	const { error, validate } = useValidation< Product >(
		property,
		async function validator() {
			if ( required && ! value ) {
				return __( 'This field is required.', 'woocommerce' );
			}
			if ( validationRegex ) {
				const regExp = new RegExp( validationRegex );
				if ( ! regExp.test( value ) ) {
					return (
						validationErrorMessage ||
						__( 'Invalid value for the field.', 'woocommerce' )
					);
				}
			}
			if ( typeof minLength === 'number' && value.length < minLength ) {
				return sprintf(
					/* translators: %d: minimum length */
					__(
						'The minimum length of the field is %d',
						'woocommerce'
					),
					minLength
				);
			}
			if ( typeof maxLength === 'number' && value.length > maxLength ) {
				return sprintf(
					/* translators: %d: maximum length */
					__(
						'The maximum length of the field is %d',
						'woocommerce'
					),
					maxLength
				);
			}
		},
		[ value ]
	);

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ nameControlId }
				label={
					required
						? createInterpolateElement( `${ label } <required/>`, {
								required: (
									<span className="woocommerce-product-form__required-input">
										{ /* translators: field 'required' indicator */ }
										{ __( '*', 'woocommerce' ) }
									</span>
								),
						  } )
						: label
				}
				className={ classNames( {
					'has-error': error,
				} ) }
				help={ error }
			>
				<InputControl
					id={ nameControlId }
					placeholder={ placeholder }
					value={ value }
					onChange={ setValue }
					onBlur={ validate }
				></InputControl>
			</BaseControl>
		</div>
	);
}
