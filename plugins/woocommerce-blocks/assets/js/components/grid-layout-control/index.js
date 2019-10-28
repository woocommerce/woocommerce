/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { clamp, isNaN } from 'lodash';
import { Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { RangeControl, ToggleControl } from '@wordpress/components';
import {
	MAX_COLUMNS,
	MIN_COLUMNS,
	MAX_ROWS,
	MIN_ROWS,
} from '@woocommerce/block-settings';

/**
 * A combination of range controls for product grid layout settings.
 */
const GridLayoutControl = ( {
	columns,
	rows,
	setAttributes,
	alignButtons,
} ) => {
	return (
		<Fragment>
			<RangeControl
				label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
				value={ columns }
				onChange={ ( value ) => {
					const newValue = clamp( value, MIN_COLUMNS, MAX_COLUMNS );
					setAttributes( {
						columns: isNaN( newValue ) ? '' : newValue,
					} );
				} }
				min={ MIN_COLUMNS }
				max={ MAX_COLUMNS }
			/>
			<RangeControl
				label={ __( 'Rows', 'woo-gutenberg-products-block' ) }
				value={ rows }
				onChange={ ( value ) => {
					const newValue = clamp( value, MIN_ROWS, MAX_ROWS );
					setAttributes( {
						rows: isNaN( newValue ) ? '' : newValue,
					} );
				} }
				min={ MIN_ROWS }
				max={ MAX_ROWS }
			/>
			<ToggleControl
				label={ __( 'Align Buttons', 'woo-gutenberg-products-block' ) }
				help={
					alignButtons
						? __(
								'Buttons are aligned vertically.',
								'woo-gutenberg-products-block'
						  )
						: __(
								'Buttons follow content.',
								'woo-gutenberg-products-block'
						  )
				}
				checked={ alignButtons }
				onChange={ () =>
					setAttributes( { alignButtons: ! alignButtons } )
				}
			/>
		</Fragment>
	);
};

GridLayoutControl.propTypes = {
	/**
	 * The current columns count.
	 */
	columns: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] )
		.isRequired,
	/**
	 * The current rows count.
	 */
	rows: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] )
		.isRequired,
	/**
	 * Whether or not buttons are aligned horizontally across items.
	 */
	alignButtons: PropTypes.bool.isRequired,
	/**
	 * Callback to update the layout settings.
	 */
	setAttributes: PropTypes.func.isRequired,
};

export default GridLayoutControl;
