/**
 * External dependencies
 */
import classNames from 'classnames';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement, useEffect, Fragment } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
	MenuGroup,
	MenuItem,
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
import { TRACKS_SOURCE } from '../../../constants';
import { handlePrompt } from '../../../utils';
import { SetListPriceMenuItem } from '../../../components/variations-table/set-list-price-menu-item';
import { VariationQuickUpdateFill } from '../../../components/variations-table';

function isPercentage( value: string ) {
	return value.endsWith( '%' );
}

function parsePercentage( value: string ) {
	const stringNumber = value.substring( 0, value.length - 1 );
	if ( Number.isNaN( Number( stringNumber ) ) ) {
		return undefined;
	}
	return Number( stringNumber );
}

export function addFixedOrPercentage(
	value: string,
	fixedOrPercentage: string,
	increaseOrDecrease: 1 | -1 = 1
) {
	if ( isPercentage( fixedOrPercentage ) ) {
		if ( Number.isNaN( Number( value ) ) ) {
			return 0;
		}
		const percentage = parsePercentage( fixedOrPercentage );
		if ( percentage === undefined ) {
			return Number( value );
		}
		return (
			Number( value ) +
			Number( value ) * ( percentage / 100 ) * increaseOrDecrease
		);
	}
	if ( Number.isNaN( Number( value ) ) ) {
		if ( Number.isNaN( Number( fixedOrPercentage ) ) ) {
			return undefined;
		}
		return Number( fixedOrPercentage );
	}
	return Number( value ) + Number( fixedOrPercentage ) * increaseOrDecrease;
}

export function Edit( {
	attributes,
	clientId,
	context,
}: ProductEditorBlockEditProps< SalePriceBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { label, help, isRequired, tooltip, disabled } = attributes;
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
		<>
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
			</div>
			<VariationQuickUpdateFill name="list-price">
				{ ( { selection, onChange, onClose } ) => {
					const ids = selection.map( ( { id } ) => id );
					return (
						<MenuGroup label={ __( 'List price', 'woocommerce' ) }>
							<SetListPriceMenuItem
								selection={ selection }
								onChange={ onChange }
								onClose={ onClose }
							/>
							<MenuItem
								onClick={ () => {
									recordEvent(
										'product_variations_menu_pricing_select',
										{
											source: TRACKS_SOURCE,
											action: 'list_price_increase',
											variation_id: ids,
										}
									);
									handlePrompt( {
										message: __(
											'Enter a value (fixed or %)',
											'woocommerce'
										),
										onOk( value ) {
											recordEvent(
												'product_variations_menu_pricing_update',
												{
													source: TRACKS_SOURCE,
													action: 'list_price_increase',
													variation_id: ids,
												}
											);
											onChange(
												selection.map(
													( {
														id,
														regular_price,
													} ) => ( {
														id,
														regular_price:
															addFixedOrPercentage(
																regular_price,
																value
															)?.toFixed( 2 ),
													} )
												)
											);
										},
									} );
									onClose();
								} }
							>
								{ __( 'Increase list price', 'woocommerce' ) }
							</MenuItem>
							<MenuItem
								onClick={ () => {
									recordEvent(
										'product_variations_menu_pricing_select',
										{
											source: TRACKS_SOURCE,
											action: 'list_price_decrease',
											variation_id: ids,
										}
									);
									handlePrompt( {
										message: __(
											'Enter a value (fixed or %)',
											'woocommerce'
										),
										onOk( value ) {
											recordEvent(
												'product_variations_menu_pricing_update',
												{
													source: TRACKS_SOURCE,
													action: 'list_price_increase',
													variation_id: ids,
												}
											);
											onChange(
												selection.map(
													( {
														id,
														regular_price,
													} ) => ( {
														id,
														regular_price:
															addFixedOrPercentage(
																regular_price,
																value,
																-1
															)?.toFixed( 2 ),
													} )
												)
											);
										},
									} );
									onClose();
								} }
							>
								{ __( 'Decrease list price', 'woocommerce' ) }
							</MenuItem>
						</MenuGroup>
					);
				} }
			</VariationQuickUpdateFill>
			<VariationQuickUpdateFill name="multiple-selections">
				{ ( { selection, onChange, onClose } ) => (
					<SetListPriceMenuItem
						selection={ selection }
						onChange={ onChange }
						onClose={ onClose }
					/>
				) }
			</VariationQuickUpdateFill>
		</>
	);
}
