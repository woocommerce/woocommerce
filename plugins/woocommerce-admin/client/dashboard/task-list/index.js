/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * Internal depdencies
 */
import './style.scss';
import { H } from '@woocommerce/components';

export default class TaskList extends Component {
	render() {
		return (
			<div className="woocommerce-task-list">
				<div className="woocommerce-task-list__header">
					<H className="woocommerce-task-list__header-title">
						{ __( 'Welcome to the WooCommerce Dashboard', 'woocommerce-admin' ) }
					</H>
					<H className="woocommerce-task-list__header-subtitle">
						{ __(
							"Here we'll guide you through the remaining tasks " +
								'to get your store ready for launch',
							'woocommerce-admin'
						) }
					</H>
				</div>
			</div>
		);
	}
}
