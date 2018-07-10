/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import SegmentedSelection from 'components/segmented-selection';

const compareValues = [
	{ value: 'previous_period', label: __( 'Previous Period', 'wc-admin' ) },
	{ value: 'previous_year', label: __( 'Previous Year', 'wc-admin' ) },
];

class ComparePeriods extends Component {
	render() {
		const { onSelect, compare } = this.props;
		return (
			<SegmentedSelection
				options={ compareValues }
				selected={ compare }
				onSelect={ onSelect }
				name="compare"
				legend={ __( 'compare to', 'wc-admin' ) }
			/>
		);
	}
}

ComparePeriods.propTypes = {
	onSelect: PropTypes.func.isRequired,
	compare: PropTypes.string,
};

export { compareValues };
export default ComparePeriods;
