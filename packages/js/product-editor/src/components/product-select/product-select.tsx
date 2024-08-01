/**
 * External dependencies
 */
import { createElement, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseControl, Spinner } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { FormattedPrice } from '../formatted-price';
import { ProductImage } from '../product-image';
import { ProductSelectProps } from './types';
import { ComboboxControl, ComboboxControlOption } from '../combobox-control';

/**
 * Map the product item to the Combobox core option.
 *
 * @param {Product} attr - Product item.
 * @return {ComboboxControlOption} Combobox option.
 */
function mapItemToOption(
	attr: Product
): ComboboxControlOption & { product?: Product } {
	return {
		label: attr.name,
		value: `attr-${ attr.id }`,
		product: attr,
	};
}

/**
 * ComboboxControlOption component.
 *
 * @return {JSX.Element} Component item.
 */
function ComboboxControlOption( props: {
	item: ComboboxControlOption & { product?: Product };
} ): JSX.Element {
	const { item } = props;
	return (
		<div className="woocommerce-product-select__menu-item">
			{ item.product && (
				<ProductImage
					product={ item.product }
					className="woocommerce-product-select__menu-item-image"
				/>
			) }
			<div className="woocommerce-product-select__menu-item-content">
				<div className="woocommerce-product-select__menu-item-title">
					{ item.label }
				</div>

				{ item.product && (
					<FormattedPrice
						product={ item.product }
						className="woocommerce-product-select__menu-item-description"
					/>
				) }
			</div>
		</div>
	);
}

export function ProductSelect( {
	className,
	label,
	help,
	placeholder,
	items = [],
	isLoading = false,
	filter,
	onSelect,
}: ProductSelectProps ) {
	const [ value, setValue ] = useState< string >( '' );
	/**
	 * Map the items to the Combobox options.
	 * Each option is an object with a label and value.
	 * Both are strings.
	 */
	const options: Array< ComboboxControlOption & { product?: Product } > =
		items?.map( mapItemToOption );

	const comboRef = useRef< HTMLInputElement | null >( null );

	// Label to link the input with the label.
	const [ labelFor, setLabelFor ] = useState< string >( '' );

	useEffect( () => {
		if ( ! comboRef?.current ) {
			return;
		}

		/*
		 * Hack to set the base control ID,
		 * to link the label with the input,
		 * picking the input ID from the ComboboxControl.
		 */
		const id = comboRef.current.getAttribute( 'id' );
		if ( comboRef.current && typeof id === 'string' ) {
			setLabelFor( id );
		}
	}, [] );

	if ( placeholder && ! help ) {
		help = placeholder;
	}

	if ( ! help ) {
		help = (
			<div className="woocommerce-product-combobox-help">
				{ __( 'Search for products', 'woocommerce' ) }
			</div>
		);

		if ( isLoading ) {
			help = (
				<div className="woocommerce-product-combobox-help">
					<Spinner />
					{ __( 'Loading…', 'woocommerce' ) }
				</div>
			);
		}
	}

	return (
		<div
			className={ classnames(
				'woocommerce-product-select',
				{
					'no-items': ! options.length,
				},
				className
			) }
		>
			<BaseControl label={ label } help={ help } id={ labelFor }>
				<ComboboxControl
					className="woocommerce-product-combobox"
					allowReset={ false }
					options={ options }
					value={ value }
					ref={ comboRef }
					onChange={ ( newValue ) => {
						if ( ! newValue ) {
							return;
						}

						const selectedProduct = items?.find(
							( item ) =>
								item.id ===
								Number( newValue.replace( 'attr-', '' ) )
						);

						if ( ! selectedProduct ) {
							return;
						}

						if ( onSelect ) {
							onSelect( selectedProduct );
						}
					} }
					onFilterValueChange={ ( searchValue: string ) => {
						setValue( searchValue );
						filter( searchValue );
					} }
					__experimentalRenderItem={ ComboboxControlOption }
				/>
			</BaseControl>
		</div>
	);
}
