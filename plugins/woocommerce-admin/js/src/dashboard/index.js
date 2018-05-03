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
import './style.scss';
import useFilters from '../use-filters';
import WidgetNumbers from './widgets/numbers';

class Dashboard extends Component {
	render() {
		return (
			<div>
				<h2>{ applyFilters( 'woodash.example2', __( 'Example Widget', 'woo-dash' ) ) }</h2>
				<WidgetNumbers />

				<h3>{ applyFilters( 'woodash.example', __( 'Example Text', 'woo-dash' ) ) }</h3>
			</div>
		);
	}
}

export default compose( [
	useFilters( [ 'woodash.example', 'woodash.example2' ] ),
] )( Dashboard );
