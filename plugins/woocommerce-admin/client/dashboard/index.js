/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Agenda from './widgets/agenda';
import Header from 'layout/header';
import StorePerformance from './store-performance';
import Charts from './charts';

export default class Dashboard extends Component {
	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Dashboard', 'wc-admin' ) ] } />
				<StorePerformance />
				<Agenda />
				<Charts />
			</Fragment>
		);
	}
}
