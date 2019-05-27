/** @format */
/**
 * External dependencies
 */
import { Component, createElement, Fragment } from '@wordpress/element';
import { pick } from 'lodash';

/**
 * Internal depdencies
 */
import ProfileWizardHeader from './header';
import Plugins from './steps/plugins';
import Start from './steps/start';
import './style.scss';
import { __ } from '@wordpress/i18n';

const getSteps = () => {
	const steps = [];
	steps.push( {
		key: 'start',
		container: Start,
	} );
	steps.push( {
		key: 'plugins',
		container: Plugins,
	} );
	steps.push( {
		key: 'store-details',
		container: Fragment,
		label: __( 'Store Details', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'industry',
		container: Fragment,
		label: __( 'Industry', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'product-types',
		container: Fragment,
		label: __( 'Product Types', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'business-details',
		container: Fragment,
		label: __( 'Business Details', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'theme',
		container: Fragment,
		label: __( 'Theme', 'woocommerce-admin' ),
	} );
	return steps;
};

export default class ProfileWizard extends Component {
	componentDidMount() {
		document.documentElement.classList.remove( 'wp-toolbar' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
	}

	componentWillUnmount() {
		document.documentElement.classList.add( 'wp-toolbar' );
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
	}

	getStep() {
		const { step } = this.props.query;
		const currentStep = getSteps().find( s => s.key === step );

		if ( ! currentStep ) {
			return getSteps()[ 0 ];
		}

		return currentStep;
	}

	render() {
		const { query } = this.props;
		const step = this.getStep();

		const container = createElement( step.container, { query } );
		const steps = getSteps().map( _step => pick( _step, [ 'key', 'label' ] ) );

		return (
			<Fragment>
				<ProfileWizardHeader currentStep={ step.key } steps={ steps } />
				<div className="woocommerce-profile-wizard__container">{ container }</div>
			</Fragment>
		);
	}
}
