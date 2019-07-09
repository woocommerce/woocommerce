/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { ToggleControl } from '@wordpress/components';

/**
 * A combination of toggle controls for content visibility in product grids.
 */
const GridContentControl = ( { onChange, settings } ) => {
	const { button, price, rating, title } = settings;
	return (
		<Fragment>
			<ToggleControl
				label={ __( 'Product title', 'woo-gutenberg-products-block' ) }
				help={
					title ?
						__( 'Product title is visible.', 'woo-gutenberg-products-block' ) :
						__( 'Product title is hidden.', 'woo-gutenberg-products-block' )
				}
				checked={ title }
				onChange={ () => onChange( { ...settings, title: ! title } ) }
			/>
			<ToggleControl
				label={ __( 'Product price', 'woo-gutenberg-products-block' ) }
				help={
					price ?
						__( 'Product price is visible.', 'woo-gutenberg-products-block' ) :
						__( 'Product price is hidden.', 'woo-gutenberg-products-block' )
				}
				checked={ price }
				onChange={ () => onChange( { ...settings, price: ! price } ) }
			/>
			<ToggleControl
				label={ __( 'Product rating', 'woo-gutenberg-products-block' ) }
				help={
					rating ?
						__( 'Product rating is visible.', 'woo-gutenberg-products-block' ) :
						__( 'Product rating is hidden.', 'woo-gutenberg-products-block' )
				}
				checked={ rating }
				onChange={ () => onChange( { ...settings, rating: ! rating } ) }
			/>
			<ToggleControl
				label={ __( 'Add to Cart button', 'woo-gutenberg-products-block' ) }
				help={
					button ?
						__(
							'Add to Cart button is visible.',
							'woo-gutenberg-products-block'
						) :
						__(
							'Add to Cart button is hidden.',
							'woo-gutenberg-products-block'
						)
				}
				checked={ button }
				onChange={ () => onChange( { ...settings, button: ! button } ) }
			/>
		</Fragment>
	);
};

GridContentControl.propTypes = {
	/**
	 * The current title visibility.
	 */
	settings: PropTypes.shape( {
		button: PropTypes.bool.isRequired,
		price: PropTypes.bool.isRequired,
		rating: PropTypes.bool.isRequired,
		title: PropTypes.bool.isRequired,
	} ).isRequired,
	/**
	 * Callback to update the layout settings.
	 */
	onChange: PropTypes.func.isRequired,
};

export default GridContentControl;
