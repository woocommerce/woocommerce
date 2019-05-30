/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component, createElement, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { pick } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal depdencies
 */
import { NAMESPACE } from 'wc-api/onboarding/constants';
import ProfileWizardHeader from './header';
import Plugins from './steps/plugins';
import Start from './steps/start';
import Industry from './steps/industry';
import StoreDetails from './steps/store-details';
import ProductTypes from './steps/product-types';
import './style.scss';

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
		container: StoreDetails,
		label: __( 'Store Details', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'industry',
		container: Industry,
		label: __( 'Industry', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'product-types',
		container: ProductTypes,
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

class ProfileWizard extends Component {
	constructor() {
		super( ...arguments );
		this.goToNextStep = this.goToNextStep.bind( this );
		this.updateProfile = this.updateProfile.bind( this );
	}

	componentDidMount() {
		document.documentElement.classList.remove( 'wp-toolbar' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
	}

	componentWillUnmount() {
		document.documentElement.classList.add( 'wp-toolbar' );
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
	}

	getCurrentStep() {
		const { step } = this.props.query;
		const currentStep = getSteps().find( s => s.key === step );

		if ( ! currentStep ) {
			return getSteps()[ 0 ];
		}

		return currentStep;
	}

	goToNextStep() {
		const currentStep = this.getCurrentStep();
		const currentStepIndex = getSteps().findIndex( s => s.key === currentStep.key );
		const nextStep = getSteps()[ currentStepIndex + 1 ];

		if ( 'undefined' === nextStep ) {
			this.updateProfile( { complete: true } );
		}

		return updateQueryString( { step: nextStep.key } );
	}

	updateProfile( params ) {
		const { addNotice } = this.props;

		return apiFetch( {
			path: `${ NAMESPACE }/onboarding/profile`,
			method: 'POST',
			data: params,
		} ).catch( error => {
			if ( error && error.message ) {
				addNotice( { status: 'error', message: error.message } );
			}
		} );
	}

	render() {
		const { query } = this.props;
		const step = this.getCurrentStep();

		const container = createElement( step.container, {
			query,
			step,
			goToNextStep: this.goToNextStep,
			updateProfile: this.updateProfile,
		} );
		const steps = getSteps().map( _step => pick( _step, [ 'key', 'label' ] ) );

		return (
			<Fragment>
				<ProfileWizardHeader currentStep={ step.key } steps={ steps } />
				<div className="woocommerce-profile-wizard__container">{ container }</div>
			</Fragment>
		);
	}
}

export default compose(
	withDispatch( dispatch => {
		const { addNotice } = dispatch( 'wc-admin' );

		return {
			addNotice,
		};
	} )
)( ProfileWizard );
