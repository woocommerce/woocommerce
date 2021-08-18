/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import SortSelect from '@woocommerce/base-components/sort-select';

/**
 * Internal dependencies
 */
import './style.scss';

const ReviewSortSelect = ( { onChange, readOnly, value } ) => {
	return (
		<SortSelect
			className="wc-block-review-sort-select wc-block-components-review-sort-select"
			label={ __( 'Order by', 'woo-gutenberg-products-block' ) }
			onChange={ onChange }
			options={ [
				{
					key: 'most-recent',
					label: __( 'Most recent', 'woo-gutenberg-products-block' ),
				},
				{
					key: 'highest-rating',
					label: __(
						'Highest rating',
						'woo-gutenberg-products-block'
					),
				},
				{
					key: 'lowest-rating',
					label: __(
						'Lowest rating',
						'woo-gutenberg-products-block'
					),
				},
			] }
			readOnly={ readOnly }
			screenReaderLabel={ __(
				'Order reviews by',
				'woo-gutenberg-products-block'
			) }
			value={ value }
		/>
	);
};

ReviewSortSelect.propTypes = {
	onChange: PropTypes.func,
	readOnly: PropTypes.bool,
	value: PropTypes.oneOf( [
		'most-recent',
		'highest-rating',
		'lowest-rating',
	] ),
};

export default ReviewSortSelect;
