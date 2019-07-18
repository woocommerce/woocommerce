/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Card, List } from '@woocommerce/components';

/**
 * Internal depdencies
 */
import './style.scss';

const getTasks = () => {
	return [
		{
			title: __( 'Connect your store to WooCommerce.com', 'woocommerce-admin' ),
			description: __(
				'Install and manage your extensions directly from your Dashboard',
				'wooocommerce-admin'
			),
			before: <i class="material-icons-outlined">extension</i>,
			after: <i class="material-icons-outlined">chevron_right</i>,
			onClick: noop,
		},
		{
			title: __( 'Add your first product', 'woocommerce-admin' ),
			description: __(
				'Add products manually, import from a sheet or migrate from another platform',
				'wooocommerce-admin'
			),
			before: <i class="material-icons-outlined">add_box</i>,
			after: <i class="material-icons-outlined">chevron_right</i>,
			onClick: noop,
		},
		{
			title: __( 'Personalize your store', 'woocommerce-admin' ),
			description: __( 'Create a custom homepage and upload your logo', 'wooocommerce-admin' ),
			before: <i class="material-icons-outlined">palette</i>,
			after: <i class="material-icons-outlined">chevron_right</i>,
			onClick: noop,
		},
		{
			title: __( 'Set up shipping', 'woocommerce-admin' ),
			description: __( 'Configure some basic shipping rates to get started', 'wooocommerce-admin' ),
			before: <i class="material-icons-outlined">local_shipping</i>,
			after: <i class="material-icons-outlined">chevron_right</i>,
			onClick: noop,
		},
		{
			title: __( 'Set up tax', 'woocommerce-admin' ),
			description: __(
				'Choose how to configure tax rates - manually or automatically',
				'wooocommerce-admin'
			),
			before: <i class="material-icons-outlined">account_balance</i>,
			after: <i class="material-icons-outlined">chevron_right</i>,
			onClick: noop,
		},
		{
			title: __( 'Set up payments', 'woocommerce-admin' ),
			description: __(
				'Select which payment providers you’d like to use and configure them',
				'wooocommerce-admin'
			),
			before: <i class="material-icons-outlined">payment</i>,
			after: <i class="material-icons-outlined">chevron_right</i>,
			onClick: noop,
		},
	];
};

export default class TaskDashboard extends Component {
	componentDidMount() {
		document.body.classList.add( 'woocommerce-task-dashboard__body' );
	}

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-task-dashboard__body' );
	}

	render() {
		return (
			<Fragment>
				<div className="woocommerce-task-dashboard__container">
					<Card
						title={ __( 'Set up your store and start selling', 'woocommerce-admin' ) }
						description={ __(
							'Below you’ll find a list of the most important steps to get your store up and running.',
							'woocommerce-admin'
						) }
					>
						<List items={ getTasks() } />
					</Card>
				</div>
			</Fragment>
		);
	}
}
