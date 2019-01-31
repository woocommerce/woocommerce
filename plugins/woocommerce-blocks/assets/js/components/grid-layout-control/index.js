/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { clamp, isNaN } from 'lodash';
import { Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { RangeControl } from '@wordpress/components';

/**
 * A combination of range controls for product grid layout settings.
 */
const GridLayoutControl = ( { columns, rows, setAttributes } ) => {
	return (
		<Fragment>
			<RangeControl
				label={ __( 'Columns', 'woo-gutenberg-products-block' ) }
				value={ columns }
				onChange={ ( value ) => {
					const newValue = clamp(
						value,
						wc_product_block_data.min_columns,
						wc_product_block_data.max_columns
					);
					setAttributes( { columns: isNaN( newValue ) ? '' : newValue } );
				} }
				min={ wc_product_block_data.min_columns }
				max={ wc_product_block_data.max_columns }
			/>
			<RangeControl
				label={ __( 'Rows', 'woo-gutenberg-products-block' ) }
				value={ rows }
				onChange={ ( value ) => {
					const newValue = clamp(
						value,
						wc_product_block_data.min_rows,
						wc_product_block_data.max_rows
					);
					setAttributes( { rows: isNaN( newValue ) ? '' : newValue } );
				} }
				min={ wc_product_block_data.min_rows }
				max={ wc_product_block_data.max_rows }
			/>
		</Fragment>
	);
};

GridLayoutControl.propTypes = {
	/**
	 * The current columns count.
	 */
	columns: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ).isRequired,
	/**
	 * The current rows count.
	 */
	rows: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ).isRequired,
	/**
	 * Callback to update the layout settings.
	 */
	setAttributes: PropTypes.func.isRequired,
};

export default GridLayoutControl;
