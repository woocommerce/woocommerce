/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
	__experimentalSelectControlMenuItem as MenuItem,
	useAsyncFilter,
	Spinner,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { FormattedPrice } from '../formatted-price';
import { ProductImage } from '../product-image';
import { ProductSelectProps } from './types';

export function ProductSelect( {
	className,
	filter,
	...props
}: ProductSelectProps ) {
	const { isFetching, ...selectProps } = useAsyncFilter< Product >( {
		filter,
	} );

	return (
		<SelectControl< Product >
			placeholder={ __( 'Search for products', 'woocommerce' ) }
			label=""
			__experimentalOpenMenuOnFocus
			{ ...props }
			{ ...selectProps }
			className={ classNames( 'woocommerce-product-select', className ) }
		>
			{ ( {
				items,
				isOpen,
				highlightedIndex,
				getMenuProps,
				getItemProps,
			} ) => (
				<Menu
					isOpen={ isOpen }
					getMenuProps={ getMenuProps }
					className="woocommerce-product-select__menu"
				>
					{ isFetching ? (
						<div className="woocommerce-product-select__menu-loading">
							<Spinner />
						</div>
					) : (
						items.map( ( item, index ) => (
							<MenuItem< Product >
								key={ item.id }
								index={ index }
								isActive={ highlightedIndex === index }
								item={ item }
								getItemProps={ ( options ) => ( {
									...getItemProps( options ),
									className:
										'woocommerce-product-select__menu-item',
								} ) }
							>
								<>
									<ProductImage
										product={ item }
										className="woocommerce-product-select__menu-item-image"
									/>
									<div className="woocommerce-product-select__menu-item-content">
										<div className="woocommerce-product-select__menu-item-title">
											{ item.name }
										</div>

										<FormattedPrice
											product={ item }
											className="woocommerce-product-select__menu-item-description"
										/>
									</div>
								</>
							</MenuItem>
						) )
					) }
				</Menu>
			) }
		</SelectControl>
	);
}
