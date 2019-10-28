/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { ToggleControl, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';

export const getSharedContentControls = ( attributes, setAttributes ) => {
	const { contentVisibility } = attributes;
	return (
		<Fragment>
			<ToggleControl
				label={ __(
					'Show Sorting Dropdown',
					'woo-gutenberg-products-block'
				) }
				checked={ contentVisibility.orderBy }
				onChange={ () =>
					setAttributes( {
						contentVisibility: {
							...contentVisibility,
							orderBy: ! contentVisibility.orderBy,
						},
					} )
				}
			/>
		</Fragment>
	);
};

export const getSharedListControls = ( attributes, setAttributes ) => {
	return (
		<Fragment>
			<SelectControl
				label={ __(
					'Order Products By',
					'woo-gutenberg-products-block'
				) }
				value={ attributes.orderby }
				options={ [
					{
						label: __(
							'Newness - newest first',
							'woo-gutenberg-products-block'
						),
						value: 'date',
					},
					{
						label: __(
							'Price - low to high',
							'woo-gutenberg-products-block'
						),
						value: 'price',
					},
					{
						label: __(
							'Price - high to low',
							'woo-gutenberg-products-block'
						),
						value: 'price-desc',
					},
					{
						label: __(
							'Rating - highest first',
							'woo-gutenberg-products-block'
						),
						value: 'rating',
					},
					{
						label: __(
							'Sales - most first',
							'woo-gutenberg-products-block'
						),
						value: 'popularity',
					},
					{
						label: __(
							'Menu Order',
							'woo-gutenberg-products-block'
						),
						value: 'menu_order',
					},
				] }
				onChange={ ( orderby ) => setAttributes( { orderby } ) }
			/>
		</Fragment>
	);
};
