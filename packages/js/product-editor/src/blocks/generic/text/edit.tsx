/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Product } from '@woocommerce/data';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { useValidation } from '../../../contexts/validation-context';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { TextBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { TextControl } from '../../../components/text-control';
import { useProductEdits } from '../../../hooks/use-product-edits';

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
		validationRegex,
		validationErrorMessage,
		minLength,
		maxLength,
		help,
		tooltip,
	} = attributes;
	const [ value, setValue ] = useProductEntityProp< string >( property, {
		postType,
		fallbackValue: '',
	} );
	const { hasEdit } = useProductEdits();
	const { error, validate } = useValidation< Product >(
		property,
		async function validator() {
			if ( typeof value !== 'string' ) {
				return __(
					'Unexpected property type assigned to field.',
					'woocommerce'
				);
			}
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
			<TextControl
				value={ value }
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
				required={ required }
				tooltip={ tooltip }
			/>
		</div>
	);
}
