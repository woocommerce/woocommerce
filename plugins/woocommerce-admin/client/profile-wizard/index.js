/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Component, createElement } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { identity, pick } from 'lodash';
import { withDispatch, withSelect } from '@wordpress/data';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import {
	NOTES_STORE_NAME,
	ONBOARDING_STORE_NAME,
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	withPluginsHydration,
	QUERY_DEFAULTS,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink, getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { BusinessDetailsStep } from './steps/business-details';
import Industry from './steps/industry';
import ProductTypes from './steps/product-types';
import ProfileWizardHeader from './header';
import StoreDetails from './steps/store-details';
import { getAdminSetting } from '~/utils/admin-settings';
import './style.scss';

const STEPS_FILTER = 'woocommerce_admin_profile_wizard_steps';

class ProfileWizard extends Component {
	constructor( props ) {
		super( props );
		this.cachedActivePlugins = props.activePlugins;
		this.goToNextStep = this.goToNextStep.bind( this );
		this.trackStepValueChanges = this.trackStepValueChanges.bind( this );
		this.updateCurrentStepValues =
			this.updateCurrentStepValues.bind( this );
		this.stepValueChanges = {};
	}

	componentDidUpdate( prevProps ) {
		const { step: prevStep } = prevProps.query;
		const { step } = this.props.query;
		const { isError, isGetProfileItemsRequesting, createNotice } =
			this.props;

		const isRequestError =
			! isGetProfileItemsRequesting && prevProps.isRequesting && isError;
		if ( isRequestError ) {
			createNotice(
				'error',
				__(
					'There was a problem finishing the setup wizard',
					'woocommerce'
				)
			);
		}

		if ( prevStep !== step ) {
			window.document.documentElement.scrollTop = 0;

			recordEvent( 'storeprofiler_step_view', {
				step: this.getCurrentStep().key,
				wc_version: getSetting( 'wcVersion' ),
			} );
		}
	}

	componentDidMount() {
		document.body.classList.remove( 'woocommerce-admin-is-loading' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );
		document.body.classList.add( 'is-wp-toolbar-disabled' );

		recordEvent( 'storeprofiler_step_view', {
			step: this.getCurrentStep().key,
			wc_version: getSetting( 'wcVersion' ),
		} );
	}

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
		document.body.classList.remove( 'woocommerce-admin-full-screen' );
		document.body.classList.remove( 'is-wp-toolbar-disabled' );
	}

	/**
	 * Set the initial and current values of a step to track the state of the step.
	 * This is used to determine if the step has been changes or not.
	 *
	 * @param {string}   step          key of the step
	 * @param {*}        initialValues the initial values of the step
	 * @param {*}        currentValues the current values of the step
	 * @param {Function} onSave        a function to call when the step is saved
	 */
	trackStepValueChanges( step, initialValues, currentValues, onSave ) {
		this.stepValueChanges[ step ] = {
			initialValues,
			currentValues,
			onSave,
		};
	}

	/**
	 * Update currentValues of the given step.
	 *
	 * @param {string} step          key of the step
	 * @param {*}      currentValues the current values of the step
	 */
	updateCurrentStepValues( step, currentValues ) {
		if ( ! this.stepValueChanges[ step ] ) {
			return;
		}
		this.stepValueChanges[ step ].currentValues = currentValues;
	}

	getSteps() {
		const { profileItems } = this.props;
		const steps = [];

		steps.push( {
			key: 'store-details',
			container: StoreDetails,
			label: __( 'Store Details', 'woocommerce' ),
			isComplete:
				profileItems.hasOwnProperty( 'setup_client' ) &&
				profileItems.setup_client !== null,
		} );
		steps.push( {
			key: 'industry',
			container: Industry,
			label: __( 'Industry', 'woocommerce' ),
			isComplete:
				profileItems.hasOwnProperty( 'industry' ) &&
				profileItems.industry !== null,
		} );
		steps.push( {
			key: 'product-types',
			container: ProductTypes,
			label: __( 'Product Types', 'woocommerce' ),
			isComplete:
				profileItems.hasOwnProperty( 'product_types' ) &&
				profileItems.product_types !== null,
		} );
		steps.push( {
			key: 'business-details',
			container: BusinessDetailsStep,
			label: __( 'Business Details', 'woocommerce' ),
			isComplete:
				profileItems.hasOwnProperty( 'product_count' ) &&
				profileItems.product_count !== null,
		} );
		/**
		 * Filter for Onboarding steps configuration.
		 *
		 * @filter woocommerce_admin_profile_wizard_steps
		 * @param {Array.<Object>} steps Array of steps for Onboarding Wizard.
		 */
		return applyFilters( STEPS_FILTER, steps );
	}

	getCurrentStep() {
		const { step } = this.props.query;
		const currentStep = this.getSteps().find( ( s ) => s.key === step );

		if ( ! currentStep ) {
			return this.getSteps()[ 0 ];
		}

		return currentStep;
	}

	/**
	 * @param {Object} tracksArgs optional track arguments for the storeprofiler_step_complete track.
	 */
	async goToNextStep( tracksArgs = {} ) {
		const { activePlugins } = this.props;
		const currentStep = this.getCurrentStep();
		const currentStepIndex = this.getSteps().findIndex(
			( s ) => s.key === currentStep.key
		);

		recordEvent( 'storeprofiler_step_complete', {
			step: currentStep.key,
			wc_version: getSetting( 'wcVersion' ),
			...tracksArgs,
		} );

		// Update the activePlugins cache in case plugins were installed
		// in the current step that affect the visibility of the next step.
		this.cachedActivePlugins = activePlugins;

		const nextStep = this.getSteps()[ currentStepIndex + 1 ];

		if ( typeof nextStep === 'undefined' ) {
			this.completeProfiler();
			return;
		}

		return updateQueryString( { step: nextStep.key } );
	}

	completeProfiler() {
		const {
			activePlugins,
			isJetpackConnected,
			notes,
			updateNote,
			updateProfileItems,
			connectToJetpack,
		} = this.props;
		recordEvent( 'storeprofiler_complete' );

		const shouldConnectJetpack =
			activePlugins.includes( 'jetpack' ) && ! isJetpackConnected;

		const profilerNote = notes.find(
			( note ) => note.name === 'wc-admin-onboarding-profiler-reminder'
		);
		if ( profilerNote ) {
			updateNote( profilerNote.id, { status: 'actioned' } );
		}

		updateProfileItems( { completed: true } ).then( () => {
			const homescreenUrl = new URL(
				getNewPath( {}, '/', {} ),
				window.location.href
			).href;

			if ( shouldConnectJetpack ) {
				document.body.classList.add( 'woocommerce-admin-is-loading' );

				connectToJetpack( () => {
					return homescreenUrl;
				} );
			} else {
				window.location.href = homescreenUrl;
			}
		} );
	}

	skipProfiler() {
		const { createNotice, updateProfileItems } = this.props;

		updateProfileItems( { skipped: true } )
			.then( () => {
				recordEvent( 'storeprofiler_store_details_skip' );
				getHistory().push( getNewPath( {}, '/', {} ) );
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem skipping the setup wizard',
						'woocommerce'
					)
				);
			} );
	}

	render() {
		const { query } = this.props;
		const step = this.getCurrentStep();
		const stepKey = step.key;
		const container = createElement( step.container, {
			query,
			step,
			goToNextStep: this.goToNextStep,
			skipProfiler: () => {
				this.skipProfiler();
			},
			trackStepValueChanges: this.trackStepValueChanges,
			updateCurrentStepValues: this.updateCurrentStepValues,
		} );
		const steps = this.getSteps().map( ( _step ) =>
			pick( _step, [ 'key', 'label', 'isComplete' ] )
		);
		const classNames = `woocommerce-profile-wizard__container ${ stepKey }`;

		return (
			<>
				<ProfileWizardHeader
					currentStep={ stepKey }
					steps={ steps }
					stepValueChanges={ this.stepValueChanges }
				/>
				<div className={ classNames }>{ container }</div>
			</>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getNotes } = select( NOTES_STORE_NAME );
		const { getProfileItems, getOnboardingError } = select(
			ONBOARDING_STORE_NAME
		);
		const { getActivePlugins, getPluginsError, isJetpackConnected } =
			select( PLUGINS_STORE_NAME );

		const profileItems = getProfileItems();

		const notesQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'update',
			status: 'unactioned',
		};
		const notes = getNotes( notesQuery );
		const activePlugins = getActivePlugins();

		return {
			getPluginsError,
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			isJetpackConnected: isJetpackConnected(),
			notes,
			profileItems,
			activePlugins,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { connectToJetpackWithFailureRedirect, createErrorNotice } =
			dispatch( PLUGINS_STORE_NAME );
		const { updateNote } = dispatch( NOTES_STORE_NAME );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		const connectToJetpack = ( failureRedirect ) => {
			connectToJetpackWithFailureRedirect(
				failureRedirect,
				createErrorNotice,
				getAdminLink
			);
		};

		return {
			connectToJetpack,
			createNotice,
			updateNote,
			updateOptions,
			updateProfileItems,
		};
	} ),
	getAdminSetting( 'plugins' )
		? withPluginsHydration( {
				...getAdminSetting( 'plugins' ),
				jetpackStatus: getAdminSetting( 'dataEndpoints', {} )
					.jetpackStatus,
		  } )
		: identity
)( ProfileWizard );
