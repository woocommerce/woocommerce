/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';

export const getSharedContentControls = ( attributes, setAttributes ) => {
	const { contentVisibility } = attributes;
	return (
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
	);
};

export const getSharedListControls = ( attributes, setAttributes ) => {
	return (
		<SelectControl
			label={ __( 'Order Products By', 'woo-gutenberg-products-block' ) }
			value={ attributes.orderby }
			options={ [
				{
					label: __(
						'Default sorting (menu order)',
						'woo-gutenberg-products-block'
					),
					value: 'menu_order',
				},
				{
					label: __( 'Popularity', 'woo-gutenberg-products-block' ),
					value: 'popularity',
				},
				{
					label: __(
						'Average rating',
						'woo-gutenberg-products-block'
					),
					value: 'rating',
				},
				{
					label: __( 'Latest', 'woo-gutenberg-products-block' ),
					value: 'date',
				},
				{
					label: __(
						'Price: low to high',
						'woo-gutenberg-products-block'
					),
					value: 'price',
				},
				{
					label: __(
						'Price: high to low',
						'woo-gutenberg-products-block'
					),
					value: 'price-desc',
				},
			] }
			onChange={ ( orderby ) => setAttributes( { orderby } ) }
		/>
	);
};
