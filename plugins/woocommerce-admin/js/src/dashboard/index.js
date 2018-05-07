/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';
import WidgetNumbers from './widgets/numbers';
import ActivityList from './widgets/activity';

export default class extends Component {
	render() {
		return (
			<div className="woo-dashboard">
				<div className="woo-dash__primary">
					<WidgetNumbers />
				</div>

				<div className="woo-dash__secondary">
					<ActivityList />
				</div>
			</div>
		);
	}
}
