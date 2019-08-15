/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const ReviewOrderSelect = ( { componentId, onChange, readOnly, value } ) => {
	const selectId = `wc-block-review-order-select__select-${ componentId }`;

	return (
		<p className="wc-block-review-order-select">
			<label className="wc-block-review-order-select__label" htmlFor={ selectId }>
				<span aria-hidden>
					{ __( 'Order by', 'woo-gutenberg-products-block' ) }
				</span>
				<span className="screen-reader-text">
					{ __( 'Order reviews by', 'woo-gutenberg-products-block' ) }
				</span>
			</label>
			<select // eslint-disable-line jsx-a11y/no-onchange
				id={ selectId }
				className="wc-block-review-order-select__select"
				onChange={ onChange }
				readOnly={ readOnly }
				value={ value }
			>
				<option value="most-recent">
					{ __( 'Most recent', 'woo-gutenberg-products-block' ) }
				</option>
				<option value="highest-rating">
					{ __( 'Highest rating', 'woo-gutenberg-products-block' ) }
				</option>
				<option value="lowest-rating">
					{ __( 'Lowest rating', 'woo-gutenberg-products-block' ) }
				</option>
			</select>
		</p>
	);
};

ReviewOrderSelect.propTypes = {
	componentId: PropTypes.number.isRequired,
	onChange: PropTypes.func,
	readOnly: PropTypes.bool,
	value: PropTypes.oneOf( [ 'most-recent', 'highest-rating', 'lowest-rating' ] ),
};

export default ReviewOrderSelect;
