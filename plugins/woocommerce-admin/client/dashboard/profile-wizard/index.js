/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
import BusinessDetails from './steps/business-details';
import Industry from './steps/industry';
import Plugins from './steps/plugins';
import ProductTypes from './steps/product-types';
import ProfileWizardHeader from './header';
import Start from './steps/start';
import StoreDetails from './steps/store-details';
import Theme from './steps/theme';
import withSelect from 'wc-api/with-select';
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
		container: BusinessDetails,
		label: __( 'Business Details', 'woocommerce-admin' ),
	} );
	steps.push( {
		key: 'theme',
		container: Theme,
		label: __( 'Theme', 'woocommerce-admin' ),
	} );
	return steps;
};

class ProfileWizard extends Component {
	constructor() {
		super( ...arguments );
		this.goToNextStep = this.goToNextStep.bind( this );
	}

	componentDidMount() {
		document.documentElement.classList.remove( 'wp-toolbar' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );
	}

	componentWillUnmount() {
		document.documentElement.classList.add( 'wp-toolbar' );
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
		document.body.classList.remove( 'woocommerce-admin-full-screen' );
	}

	getCurrentStep() {
		const { step } = this.props.query;
		const currentStep = getSteps().find( s => s.key === step );

		if ( ! currentStep ) {
			return getSteps()[ 0 ];
		}

		return currentStep;
	}

	async goToNextStep() {
		const { createNotice, isError, updateProfileItems } = this.props;
		const currentStep = this.getCurrentStep();
		const currentStepIndex = getSteps().findIndex( s => s.key === currentStep.key );
		const nextStep = getSteps()[ currentStepIndex + 1 ];

		if ( 'undefined' === typeof nextStep ) {
			await updateProfileItems( { completed: true } );

			if ( isError ) {
				createNotice(
					'error',
					__( 'There was a problem completing the profiler.', 'woocommerce-admin' )
				);
			}
			return;
		}

		return updateQueryString( { step: nextStep.key } );
	}

	render() {
		const { query } = this.props;
		const step = this.getCurrentStep();

		const container = createElement( step.container, {
			query,
			step,
			goToNextStep: this.goToNextStep,
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
	withSelect( select => {
		const { getProfileItemsError } = select( 'wc-api' );

		const isError = Boolean( getProfileItemsError() );

		return { isError };
	} ),
	withDispatch( dispatch => {
		const { updateProfileItems } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( ProfileWizard );
