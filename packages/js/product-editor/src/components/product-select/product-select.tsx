/**
 * External dependencies
 */
import { createElement, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	ComboboxControl as CoreComboboxControl,
	Spinner,
} from '@wordpress/components';
import { Product } from '@woocommerce/data';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { FormattedPrice } from '../formatted-price';
import { ProductImage } from '../product-image';
import {
	ComboboxControlProductSelectOption,
	ProductSelectProps,
} from './types';
import { ComboboxControlOption } from '../attribute-combobox-field/types';

interface ComboboxControlProps
	extends Omit< CoreComboboxControl.Props, 'label' | 'help' > {
	__experimentalRenderItem?: ( args: {
		item: ComboboxControlProductSelectOption;
	} ) => string | JSX.Element;
}

/*
 * Create an alias for the ComboboxControl core component,
 * but with the custom ComboboxControlProps interface.
 */
const ComboboxControl =
	CoreComboboxControl as React.ComponentType< ComboboxControlProps >;

/**
 * Map the product attribute item to the Combobox core option.
 *
 * @param {Product} attr - Product attribute item.
 * @return {ComboboxControlOption}               Combobox option.
 */
function mapItemToOption( attr: Product ): ComboboxControlProductSelectOption {
	return {
		label: attr.name,
		value: `attr-${ attr.id }`,
		product: attr,
	};
}

/**
 * ComboboxControlOption component.
 *
 * @return {JSX.Element}                       Component item.
 */
function ComboboxControlOption( props: {
	item: ComboboxControlProductSelectOption;
} ): JSX.Element {
	const { item } = props;
	return (
		<div className="woocommerce-product-select__menu-item">
			<ProductImage
				product={ item.product }
				className="woocommerce-product-select__menu-item-image"
			/>
			<div className="woocommerce-product-select__menu-item-content">
				<div className="woocommerce-product-select__menu-item-title">
					{ item.label }
				</div>

				<FormattedPrice
					product={ item.product }
					className="woocommerce-product-select__menu-item-description"
				/>
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
	instanceNumber = 0,
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
	const options: ComboboxControlOption[] = items?.map( mapItemToOption );

	const comboRef = useRef< HTMLDivElement | null >( null );

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
		const inputElement = comboRef.current.querySelector(
			'input.components-combobox-control__input'
		);

		const id = inputElement?.getAttribute( 'id' );
		if ( inputElement && typeof id === 'string' ) {
			setLabelFor( id );
		}

		/*
		 * Hack to handle AttributesComboboxControl instances z index,
		 * avoiding to overlap the dropdown instances list.
		 * Todo: this is a temporary/not-ideal solution.
		 * It should be handled by the core ComboboxControl component.
		 */
		const listContainerElement = comboRef.current.querySelector(
			'.components-combobox-control__suggestions-container'
		) as HTMLElement;
		const style = { zIndex: 1000 - instanceNumber };

		if ( listContainerElement ) {
			Object.assign( listContainerElement.style, style );
		}
	}, [ instanceNumber ] );

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
			ref={ comboRef }
		>
			<BaseControl label={ label } help={ help } id={ labelFor }>
				<ComboboxControl
					className="woocommerce-product-combobox"
					allowReset={ false }
					options={ options }
					value={ value }
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
