/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';

/**
 * `ChartPlaceholder` displays a large loading indiciator for use in place of a `Chart` while data is loading.
 */
class ChartPlaceholder extends Component {
	render() {
		return <div className="woocommerce-chart is-placeholder" aria-hidden="true" />;
	}
}

export default ChartPlaceholder;
