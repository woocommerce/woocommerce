/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, createElement, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { identity, pick } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	withSettingsHydration,
	withPluginsHydration,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import Benefits from './steps/benefits';
import BusinessDetails from './steps/business-details';
import Industry from './steps/industry';
import ProductTypes from './steps/product-types';
import ProfileWizardHeader from './header';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import { recordEvent } from 'lib/tracks';
import StoreDetails from './steps/store-details';
import Theme from './steps/theme';
import withSelect from 'wc-api/with-select';
import './style.scss';

class ProfileWizard extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			cartRedirectUrl: null,
		};

		this.activePlugins = props.activePlugins;
		this.goToNextStep = this.goToNextStep.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { step: prevStep } = prevProps.query;
		const { step } = this.props.query;
		const {
			isError,
			isGetProfileItemsRequesting,
			createNotice,
		} = this.props;

		const isRequestError =
			! isGetProfileItemsRequesting && prevProps.isRequesting && isError;
		if ( isRequestError ) {
			createNotice(
				'error',
				__(
					'There was a problem finishing the profile wizard.',
					'woocommerce-admin'
				)
			);
		}

		if ( prevStep !== step ) {
			window.document.documentElement.scrollTop = 0;

			recordEvent( 'storeprofiler_step_view', {
				step: this.getCurrentStep().key,
			} );
		}
	}

	componentDidMount() {
		const { profileItems, updateProfileItems } = this.props;

		document.body.classList.remove( 'woocommerce-admin-is-loading' );
		document.documentElement.classList.remove( 'wp-toolbar' );
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );

		recordEvent( 'storeprofiler_step_view', {
			step: this.getCurrentStep().key,
		} );

		// Track plugins if already installed.
		if (
			this.activePlugins.includes( 'woocommerce-services' ) &&
			this.activePlugins.includes( 'jetpack' ) &&
			profileItems.plugins !== 'already-installed'
		) {
			recordEvent(
				'wcadmin_storeprofiler_already_installed_plugins',
				{}
			);

			updateProfileItems( { plugins: 'already-installed' } );
		}
	}

	componentWillUnmount() {
		const { cartRedirectUrl } = this.state;

		if ( cartRedirectUrl ) {
			document.body.classList.add( 'woocommerce-admin-is-loading' );
			window.location = cartRedirectUrl;
		}

		document.documentElement.classList.add( 'wp-toolbar' );
		document.body.classList.remove( 'woocommerce-onboarding' );
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
		document.body.classList.remove( 'woocommerce-admin-full-screen' );
	}

	getSteps() {
		const { profileItems } = this.props;
		const steps = [];

		steps.push( {
			key: 'store-details',
			container: StoreDetails,
			label: __( 'Store Details', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'setup_client' ) &&
				profileItems.setup_client !== null,
		} );
		steps.push( {
			key: 'industry',
			container: Industry,
			label: __( 'Industry', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'industry' ) &&
				profileItems.industry !== null,
		} );
		steps.push( {
			key: 'product-types',
			container: ProductTypes,
			label: __( 'Product Types', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'product_types' ) &&
				profileItems.product_types !== null,
		} );
		steps.push( {
			key: 'business-details',
			container: BusinessDetails,
			label: __( 'Business Details', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'product_count' ) &&
				profileItems.product_count !== null,
		} );
		steps.push( {
			key: 'theme',
			container: Theme,
			label: __( 'Theme', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'theme' ) &&
				profileItems.theme !== null,
		} );

		if (
			! this.activePlugins.includes( 'woocommerce-services' ) ||
			! this.activePlugins.includes( 'jetpack' )
		) {
			steps.push( {
				key: 'benefits',
				container: Benefits,
			} );
		}
		return steps;
	}

	getCurrentStep() {
		const { step } = this.props.query;
		const currentStep = this.getSteps().find( ( s ) => s.key === step );

		if ( ! currentStep ) {
			return this.getSteps()[ 0 ];
		}

		return currentStep;
	}

	async goToNextStep() {
		const currentStep = this.getCurrentStep();
		const currentStepIndex = this.getSteps().findIndex(
			( s ) => s.key === currentStep.key
		);

		recordEvent( 'storeprofiler_step_complete', {
			step: currentStep.key,
		} );

		const nextStep = this.getSteps()[ currentStepIndex + 1 ];
		if ( typeof nextStep === 'undefined' ) {
			this.completeProfiler();
			return;
		}

		return updateQueryString( { step: nextStep.key } );
	}

	completeProfiler() {
		const { notes, updateNote, updateProfileItems } = this.props;
		updateProfileItems( { completed: true } );
		recordEvent( 'storeprofiler_complete' );

		const profilerNote = notes.find(
			( note ) => note.name === 'wc-admin-onboarding-profiler-reminder'
		);
		if ( profilerNote ) {
			updateNote( profilerNote.id, { status: 'actioned' } );
		}
	}

	render() {
		const { query } = this.props;
		const step = this.getCurrentStep();

		const container = createElement( step.container, {
			query,
			step,
			goToNextStep: this.goToNextStep,
		} );
		const steps = this.getSteps().map( ( _step ) =>
			pick( _step, [ 'key', 'label', 'isComplete' ] )
		);

		return (
			<Fragment>
				<ProfileWizardHeader currentStep={ step.key } steps={ steps } />
				<div className="woocommerce-profile-wizard__container">
					{ container }
				</div>
			</Fragment>
		);
	}
}

const hydrateSettings =
	window.wcSettings.preloadSettings &&
	window.wcSettings.preloadSettings.general;

export default compose(
	withSelect( ( select ) => {
		const { getNotes } = select( 'wc-api' );
		const { getProfileItems, getOnboardingError } = select( ONBOARDING_STORE_NAME );
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		const notesQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'update',
			status: 'unactioned',
		};
		const notes = getNotes( notesQuery );
		const activePlugins = getActivePlugins();

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			notes,
			profileItems: getProfileItems(),
			activePlugins,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateNote } = dispatch( 'wc-api' );
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateNote,
			updateProfileItems,
		};
	} ),
	hydrateSettings
		? withSettingsHydration( 'general', {
				general: window.wcSettings.preloadSettings.general,
		  } )
		: identity,
	window.wcSettings.plugins
		? withPluginsHydration( {
				...window.wcSettings.plugins,
				jetpackStatus: window.wcSettings.dataEndpoints.jetpackStatus,
		  } )
		: identity
)( ProfileWizard );
