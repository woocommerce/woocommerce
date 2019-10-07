/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { filter, get } from 'lodash';
import { compose } from '@wordpress/compose';

/**
 * WooCommerce dependencies
 */
import { Card, List } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal depdencies
 */
import './style.scss';
import Appearance from './tasks/appearance';
import Connect from './tasks/connect';
import Products from './tasks/products';
import Shipping from './tasks/shipping';
import Tax from './tasks/tax';
import Payments from './tasks/payments';
import withSelect from 'wc-api/with-select';
import { recordEvent } from 'lib/tracks';

const {
	customLogo,
	hasHomepage,
	hasPhysicalProducts,
	hasProducts,
	shippingZonesCount,
} = getSetting( 'onboarding', {
	customLogo: '',
	hasHomePage: false,
	hasPhysicalProducts: false,
	hasProducts: false,
	shippingZonesCount: 0,
} );

class TaskDashboard extends Component {
	componentDidMount() {
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-task-dashboard__body' );

		this.recordEvent();
	}

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-onboarding' );
		document.body.classList.remove( 'woocommerce-task-dashboard__body' );
	}

	recordEvent() {
		if ( this.getCurrentTask() ) {
			return;
		}
		const { profileItems } = this.props;
		const tasks = filter( this.getTasks(), task => task.visible );
		recordEvent( 'tasklist_view', {
			number_tasks: tasks.length,
			store_connected: profileItems.wccom_connected,
		} );
	}

	getTasks() {
		const { profileItems, query, paymentsCompleted } = this.props;

		return [
			{
				key: 'connect',
				title: __( 'Connect your store to WooCommerce.com', 'woocommerce-admin' ),
				content: __(
					'Install and manage your extensions directly from your Dashboard',
					'wooocommerce-admin'
				),
				before: <i className="material-icons-outlined">extension</i>,
				after: <i className="material-icons-outlined">chevron_right</i>,
				onClick: () => updateQueryString( { task: 'connect' } ),
				container: <Connect query={ query } />,
				visible: profileItems.items_purchased && ! profileItems.wccom_connected,
			},
			{
				key: 'products',
				title: __( 'Add your first product', 'woocommerce-admin' ),
				content: __(
					'Add products manually, import from a sheet or migrate from another platform',
					'wooocommerce-admin'
				),
				before: hasProducts ? (
					<i className="material-icons-outlined">check_circle</i>
				) : (
					<i className="material-icons-outlined">add_box</i>
				),
				after: <i className="material-icons-outlined">chevron_right</i>,
				onClick: () => updateQueryString( { task: 'products' } ),
				container: <Products />,
				className: hasProducts ? 'is-complete' : null,
				visible: true,
			},
			{
				key: 'appearance',
				title: __( 'Personalize your store', 'woocommerce-admin' ),
				content: __( 'Create a custom homepage and upload your logo', 'wooocommerce-admin' ),
				before: <i className="material-icons-outlined">palette</i>,
				after: <i className="material-icons-outlined">chevron_right</i>,
				onClick: () => updateQueryString( { task: 'appearance' } ),
				container: <Appearance />,
				className: customLogo && hasHomepage ? 'is-complete' : null,
				visible: true,
			},
			{
				key: 'shipping',
				title: __( 'Set up shipping', 'woocommerce-admin' ),
				content: __( 'Configure some basic shipping rates to get started', 'wooocommerce-admin' ),
				before:
					shippingZonesCount > 0 ? (
						<i className="material-icons-outlined">check_circle</i>
					) : (
						<i className="material-icons-outlined">local_shipping</i>
					),
				after: <i className="material-icons-outlined">chevron_right</i>,
				onClick: () => updateQueryString( { task: 'shipping' } ),
				container: <Shipping />,
				className: shippingZonesCount > 0 ? 'is-complete' : null,
				visible: profileItems.product_types.includes( 'physical' ) || hasPhysicalProducts,
			},
			{
				key: 'tax',
				title: __( 'Set up tax', 'woocommerce-admin' ),
				content: __(
					'Choose how to configure tax rates - manually or automatically',
					'wooocommerce-admin'
				),
				before: <i className="material-icons-outlined">account_balance</i>,
				after: <i className="material-icons-outlined">chevron_right</i>,
				onClick: () => updateQueryString( { task: 'tax' } ),
				container: <Tax />,
				visible: true,
			},
			{
				key: 'payments',
				title: __( 'Set up payments', 'woocommerce-admin' ),
				content: __(
					'Select which payment providers you’d like to use and configure them',
					'wooocommerce-admin'
				),
				before: <i className="material-icons-outlined">payment</i>,
				after: <i className="material-icons-outlined">chevron_right</i>,
				onClick: () => updateQueryString( { task: 'payments' } ),
				container: <Payments />,
				className: paymentsCompleted ? 'is-complete' : null,
				visible: true,
			},
		];
	}

	getCurrentTask() {
		const { task } = this.props.query;
		const currentTask = this.getTasks().find( s => s.key === task );

		if ( ! currentTask ) {
			return null;
		}

		return currentTask;
	}

	render() {
		const currentTask = this.getCurrentTask();
		const tasks = filter( this.getTasks(), task => task.visible );

		return (
			<Fragment>
				<div className="woocommerce-task-dashboard__container">
					{ currentTask ? (
						currentTask.container
					) : (
						<Card
							className="woocommerce-task-card"
							title={ __( 'Set up your store and start selling', 'woocommerce-admin' ) }
							description={ __(
								'Below you’ll find a list of the most important steps to get your store up and running.',
								'woocommerce-admin'
							) }
						>
							<List items={ tasks } />
						</Card>
					) }
				</div>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getProfileItems, getOptions } = select( 'wc-api' );
		const profileItems = getProfileItems();

		const options = getOptions( [ 'woocommerce_onboarding_payments' ] );
		const paymentsCompleted = get(
			options,
			[ 'woocommerce_onboarding_payments', 'completed' ],
			false
		);

		return { profileItems, paymentsCompleted };
	} )
)( TaskDashboard );
