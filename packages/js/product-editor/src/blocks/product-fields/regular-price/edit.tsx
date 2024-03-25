/**
 * External dependencies
 */
import classNames from 'classnames';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { InspectorControls } from '@wordpress/block-editor';
import { Product } from '@woocommerce/data';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement, useEffect } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';
import {
	BaseControl,
	PanelBody,
	TextControl,
	ToggleControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Label } from '../../../components/label/label';
import { useValidation } from '../../../contexts/validation-context';
import { useCurrencyInputProps } from '../../../hooks/use-currency-input-props';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import type { ProductEditorBlockEditProps } from '../../../types';
import type { SalePriceBlockAttributes } from './types';

export function Edit( {
	attributes,
	clientId,
	context,
	setAttributes,
}: ProductEditorBlockEditProps< SalePriceBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label, help = '', isRequired, tooltip, disabled } = attributes;
	const [ regularPrice, setRegularPrice ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		'regular_price'
	);
	const [ salePrice ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		'sale_price'
	);
	const inputProps = useCurrencyInputProps( {
		value: regularPrice,
		onChange: setRegularPrice,
	} );

	function renderHelp() {
		if ( help ) {
			return <span dangerouslySetInnerHTML={ sanitizeHTML( help ) } />;
		}
	}

	const regularPriceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-regular-price-field'
	) as string;

	const {
		ref: regularPriceRef,
		error: regularPriceValidationError,
		validate: validateRegularPrice,
	} = useValidation< Product >(
		`regular_price-${ clientId }`,
		async function regularPriceValidator() {
			const listPrice = Number.parseFloat( regularPrice );
			if ( listPrice ) {
				if ( listPrice < 0 ) {
					return __(
						'List price must be greater than or equals to zero.',
						'woocommerce'
					);
				}
				if (
					salePrice &&
					listPrice <= Number.parseFloat( salePrice )
				) {
					return __(
						'List price must be greater than the sale price.',
						'woocommerce'
					);
				}
			} else if ( isRequired ) {
				/* translators: label of required field. */
				return sprintf( __( '%s is required.', 'woocommerce' ), label );
			}
		},
		[ regularPrice, salePrice ]
	);

	useEffect( () => {
		if ( isRequired ) {
			validateRegularPrice();
		}
	}, [] );

	return (
		<div { ...blockProps }>
			<BaseControl
				id={ regularPriceId }
				help={
					regularPriceValidationError
						? regularPriceValidationError
						: renderHelp()
				}
				className={ classNames( {
					'has-error': regularPriceValidationError,
				} ) }
			>
				<InputControl
					{ ...inputProps }
					id={ regularPriceId }
					name={ 'regular_price' }
					ref={ regularPriceRef }
					label={
						tooltip ? (
							<Label label={ label } tooltip={ tooltip } />
						) : (
							label
						)
					}
					disabled={ disabled }
					onBlur={ validateRegularPrice }
				/>
			</BaseControl>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<TextControl
						label={ __( 'Label', 'woocommerce' ) }
						value={ label }
						onChange={ ( newValue ) =>
							setAttributes( { label: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Help', 'woocommerce' ) }
						value={ help }
						onChange={ ( newValue ) =>
							setAttributes( { help: newValue } )
						}
					/>
					<TextControl
						label={ __( 'Tooltip', 'woocommerce' ) }
						value={ tooltip || '' }
						onChange={ ( newValue ) =>
							setAttributes( { tooltip: newValue } )
						}
					/>
					<ToggleControl
						label={ __( 'Required', 'woocommerce' ) }
						checked={ isRequired }
						onChange={ ( newValue ) =>
							setAttributes( { isRequired: newValue } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
}
