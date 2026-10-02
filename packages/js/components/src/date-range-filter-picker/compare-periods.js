/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Component } from '@wordpress/element';
import PropTypes from 'prop-types';

import { getComparePeriods } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import SegmentedSelection from '../segmented-selection';

class ComparePeriods extends Component {
	render() {
		const { onSelect, compare, period, after, before } = this.props;
		const options = getComparePeriods( period, after, before );
		const selected = options.some( ( option ) => option.value === compare )
			? compare
			: options[ 0 ].value;
		return (
			<SegmentedSelection
				options={ options }
				selected={ selected }
				onSelect={ onSelect }
				name="compare"
				legend={ __( 'compare to', 'woocommerce' ) }
			/>
		);
	}
}

ComparePeriods.propTypes = {
	onSelect: PropTypes.func.isRequired,
	compare: PropTypes.string,
	period: PropTypes.string,
	after: PropTypes.object,
	before: PropTypes.object,
};

export default ComparePeriods;
