/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { useMergeRefs } from '@wordpress/compose';
import { Link } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { createElement, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { TextControl } from '../../../components/text-control';
import { useValidation } from '../../../contexts/validation-context';
import { useProductEdits } from '../../../hooks/use-product-edits';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductEditorBlockEditProps } from '../../../types';
import { TextBlockAttributes } from './types';

export function Edit( {
	attributes,
	context: { postType },
}: ProductEditorBlockEditProps< TextBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const {
		property,
		label,
		placeholder,
		required,
		pattern,
		minLength,
		maxLength,
		min,
		max,
		help,
		tooltip,
		disabled,
		type,
		suffix,
	} = attributes;

	const [ value, setValue ] = useProductEntityProp< string >( property, {
		postType,
		fallbackValue: '',
	} );

	const { hasEdit } = useProductEdits();

	const inputRef = useRef< HTMLInputElement >( null );

	const {
		error,
		validate,
		ref: inputValidatorRef,
	} = useValidation< Product >(
		property,
		async function validator() {
			if ( ! inputRef.current ) return;

			const input = inputRef.current;

			let customErrorMessage = '';

			if ( input.validity.typeMismatch ) {
				customErrorMessage =
					type?.message ??
					__( 'Invalid value for the field.', 'woocommerce' );
			}
			if ( input.validity.valueMissing ) {
				customErrorMessage =
					typeof required === 'string'
						? required
						: __( 'This field is required.', 'woocommerce' );
			}
			if ( input.validity.patternMismatch ) {
				customErrorMessage =
					pattern?.message ??
					__( 'Invalid value for the field.', 'woocommerce' );
			}
			if ( input.validity.tooShort ) {
				// eslint-disable-next-line @wordpress/valid-sprintf
				customErrorMessage = sprintf(
					minLength?.message ??
						/* translators: %d: minimum length */
						__(
							'The minimum length of the field is %d',
							'woocommerce'
						),
					minLength?.value
				);
			}
			if ( input.validity.tooLong ) {
				// eslint-disable-next-line @wordpress/valid-sprintf
				customErrorMessage = sprintf(
					maxLength?.message ??
						/* translators: %d: maximum length */
						__(
							'The maximum length of the field is %d',
							'woocommerce'
						),
					maxLength?.value
				);
			}
			if ( input.validity.rangeUnderflow ) {
				// eslint-disable-next-line @wordpress/valid-sprintf
				customErrorMessage = sprintf(
					min?.message ??
						/* translators: %d: minimum length */
						__(
							'The minimum value of the field is %d',
							'woocommerce'
						),
					min?.value
				);
			}
			if ( input.validity.rangeOverflow ) {
				// eslint-disable-next-line @wordpress/valid-sprintf
				customErrorMessage = sprintf(
					max?.message ??
						/* translators: %d: maximum length */
						__(
							'The maximum value of the field is %d',
							'woocommerce'
						),
					max?.value
				);
			}

			input.setCustomValidity( customErrorMessage );

			if ( ! input.validity.valid ) {
				return {
					message: customErrorMessage,
				};
			}
		},
		[ type, required, pattern, minLength, maxLength, min, max ]
	);

	function getSuffix() {
		if ( ! suffix || ! value || ! inputRef.current ) return;

		const isValidUrl =
			inputRef.current.type === 'url' &&
			! inputRef.current.validity.typeMismatch;

		if ( suffix === true && isValidUrl ) {
			return (
				<Link
					type="external"
					href={ value }
					target="_blank"
					rel="noreferrer"
					className="wp-block-woocommerce-product-text-field__suffix-link"
				>
					<Icon icon={ external } size={ 20 } />
				</Link>
			);
		}

		return typeof suffix === 'string' ? suffix : undefined;
	}

	return (
		<div { ...blockProps }>
			<TextControl
				ref={ useMergeRefs( [ inputRef, inputValidatorRef ] ) }
				type={ type?.value ?? 'text' }
				value={ value }
				disabled={ disabled }
				label={ label }
				onChange={ setValue }
				onBlur={ () => {
					if ( hasEdit( property ) ) {
						validate();
					}
				} }
				error={ error }
				help={ help }
				placeholder={ placeholder }
				tooltip={ tooltip }
				suffix={ getSuffix() }
				required={ Boolean( required ) }
				pattern={ pattern?.value }
				minLength={ minLength?.value }
				maxLength={ maxLength?.value }
				min={ min?.value }
				max={ max?.value }
			/>
		</div>
	);
}
