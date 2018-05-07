/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Component, compose } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useFilters from '../../use-filters';

class Activity extends Component {
	render() {
		return (
			<div className="woo-dash__activity">
				<h2>{ applyFilters( 'woodash.example', __( 'Activity', 'woo-dash' ) ) }</h2>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 250 250">
					<path fill="#D8D8D8" d="M10 130h50v120H10zm60-24h50v144H70zm60 45h50v99h-50zm60-84h50v183h-50z" />
				</svg>
			</div>
		);
	}
}

export default compose( [
	useFilters( [ 'woodash.example' ] ),
] )( Activity );
