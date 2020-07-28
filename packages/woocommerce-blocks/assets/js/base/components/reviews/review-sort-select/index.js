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
			className="wc-block-review-sort-select wc-block-components-review-sort-select"
			defaultValue={ defaultValue }
			label={ __( 'Order by', 'woocommerce' ) }
			onChange={ onChange }
			options={ [
				{
					key: 'most-recent',
					label: __( 'Most recent', 'woocommerce' ),
				},
				{
					key: 'highest-rating',
					label: __(
						'Highest rating',
						'woocommerce'
					),
				},
				{
					key: 'lowest-rating',
					label: __(
						'Lowest rating',
						'woocommerce'
					),
				},
			] }
			readOnly={ readOnly }
			screenReaderLabel={ __(
				'Order reviews by',
				'woocommerce'
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
