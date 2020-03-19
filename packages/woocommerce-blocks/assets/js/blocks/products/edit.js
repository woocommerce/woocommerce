/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './editor.scss';

export const getSharedContentControls = ( attributes, setAttributes ) => {
	const { contentVisibility } = attributes;
	return (
		<ToggleControl
			label={ __(
				'Show Sorting Dropdown',
				'woocommerce'
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
			label={ __( 'Order Products By', 'woocommerce' ) }
			value={ attributes.orderby }
			options={ [
				{
					label: __(
						'Newness - newest first',
						'woocommerce'
					),
					value: 'date',
				},
				{
					label: __(
						'Price - low to high',
						'woocommerce'
					),
					value: 'price',
				},
				{
					label: __(
						'Price - high to low',
						'woocommerce'
					),
					value: 'price-desc',
				},
				{
					label: __(
						'Rating - highest first',
						'woocommerce'
					),
					value: 'rating',
				},
				{
					label: __(
						'Sales - most first',
						'woocommerce'
					),
					value: 'popularity',
				},
				{
					label: __( 'Menu Order', 'woocommerce' ),
					value: 'menu_order',
				},
			] }
			onChange={ ( orderby ) => setAttributes( { orderby } ) }
		/>
	);
};
