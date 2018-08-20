/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { filterPaths, filters } from './constants';
import Header from 'layout/header';
import { ReportFilters } from '@woocommerce/components';
import './style.scss';

export default class extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'wc-admin' ) ],
						__( 'Products', 'wc-admin' ),
					] }
				/>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					filterPaths={ filterPaths }
				/>
			</Fragment>
		);
	}
}
