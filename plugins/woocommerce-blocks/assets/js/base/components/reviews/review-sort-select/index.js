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

const ReviewSortSelect = ( { defaultValue, onChange, readOnly, value } ) => {
	return (
		<SortSelect
			className="wc-block-review-sort-select"
			defaultValue={ defaultValue }
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
	defaultValue: PropTypes.oneOf( [
		'most-recent',
		'highest-rating',
		'lowest-rating',
	] ),
	onChange: PropTypes.func,
	readOnly: PropTypes.bool,
	value: PropTypes.oneOf( [
		'most-recent',
		'highest-rating',
		'lowest-rating',
	] ),
};

export default ReviewSortSelect;
