/**
 * External dependencies
 */
import classNames from 'classnames';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Link } from '@woocommerce/components';
import { createElement, useRef, useState } from '@wordpress/element';
import { Icon, external } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { TextControl } from '../../../components/text-control';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { ProductEditorBlockEditProps } from '../../../types';
import { TextBlockAttributes } from './types';

export function Edit( {
	attributes,
	context: { postType, validationErrors },
}: ProductEditorBlockEditProps< TextBlockAttributes > ) {
	const [ isTouched, setIsTouched ] = useState< boolean >( false );
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

	const inputRef = useRef< HTMLInputElement >( null );

	const error = validationErrors[ property ];

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
				ref={ inputRef }
				type={ type?.value ?? 'text' }
				value={ value }
				disabled={ disabled }
				label={ label }
				onChange={ setValue }
				error={ !! isTouched && !! error ? error.message : undefined }
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
				onBlur={ () => setIsTouched( true ) }
			/>
		</div>
	);
}
