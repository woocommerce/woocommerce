/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Benefits from './steps/benefits';
import { BusinessDetailsStep } from './steps/business-details';
import Industry from './steps/industry';
import ProductTypes from './steps/product-types';
import ProfileWizardHeader from './header';
import StoreDetails from './steps/store-details';
import Theme from './steps/theme';
import './style.scss';
import { isSelectiveBundleInstallSegmentation } from './steps/business-details/data/segmentation';

class ProfileWizard extends Component {
	constructor( props ) {
		super( props );
		this.cachedActivePlugins = props.activePlugins;
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
					'There was a problem finishing the setup wizard',
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
		document.body.classList.remove( 'woocommerce-admin-is-loading' );
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );
		document.body.classList.add( 'is-wp-toolbar-disabled' );

		recordEvent( 'storeprofiler_step_view', {
			step: this.getCurrentStep().key,
		} );
	}

	componentWillUnmount() {
		document.body.classList.remove( 'woocommerce-onboarding' );
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
		document.body.classList.remove( 'woocommerce-admin-full-screen' );
		document.body.classList.remove( 'is-wp-toolbar-disabled' );
	}

	getSteps() {
		const {
			profileItems,
			query,
			selectiveBundleInstallSegmentation,
		} = this.props;
		const { step } = query;
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
			key: selectiveBundleInstallSegmentation
				? 'business-features'
				: 'business-details',
			container: BusinessDetailsStep,
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
			! selectiveBundleInstallSegmentation &&
			( ! this.cachedActivePlugins.includes( 'woocommerce-services' ) ||
				! this.cachedActivePlugins.includes( 'jetpack' ) ||
				step === 'benefits' )
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
		const { activePlugins, dismissedTasks, updateOptions } = this.props;
		const currentStep = this.getCurrentStep();
		const currentStepIndex = this.getSteps().findIndex(
			( s ) => s.key === currentStep.key
		);

		recordEvent( 'storeprofiler_step_complete', {
			step: currentStep.key,
		} );

		if ( dismissedTasks.length ) {
			updateOptions( {
				woocommerce_task_list_dismissed_tasks: [],
			} );
		}

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
						'woocommerce-admin'
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
		} );
		const steps = this.getSteps().map( ( _step ) =>
			pick( _step, [ 'key', 'label', 'isComplete' ] )
		);
		const classNames = `woocommerce-profile-wizard__container ${ stepKey }`;

		return (
			<>
				<ProfileWizardHeader currentStep={ stepKey } steps={ steps } />
				<div className={ classNames }>{ container }</div>
			</>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getNotes } = select( NOTES_STORE_NAME );
		const { getOption } = select( OPTIONS_STORE_NAME );
		const { getProfileItems, getOnboardingError } = select(
			ONBOARDING_STORE_NAME
		);
		const {
			getActivePlugins,
			getPluginsError,
			isJetpackConnected,
		} = select( PLUGINS_STORE_NAME );
		const { general: generalSettings } = select(
			SETTINGS_STORE_NAME
		).getSettings( 'general' );

		const profileItems = getProfileItems();
		const country = generalSettings.woocommerce_default_country || null;
		const industrySlugs = ( profileItems.industry || [] ).map(
			( industry ) => industry.slug
		);

		const selectiveBundleInstallSegmentation = isSelectiveBundleInstallSegmentation(
			country,
			industrySlugs
		);

		const notesQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'update',
			status: 'unactioned',
		};
		const notes = getNotes( notesQuery );
		const activePlugins = getActivePlugins();
		const dismissedTasks =
			getOption( 'woocommerce_task_list_dismissed_tasks' ) || [];

		return {
			dismissedTasks,
			getPluginsError,
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			isJetpackConnected: isJetpackConnected(),
			notes,
			profileItems,
			activePlugins,
			selectiveBundleInstallSegmentation,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const {
			connectToJetpackWithFailureRedirect,
			createErrorNotice,
		} = dispatch( PLUGINS_STORE_NAME );
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
	window.wcSettings.plugins
		? withPluginsHydration( {
				...window.wcSettings.plugins,
				jetpackStatus: window.wcSettings.dataEndpoints.jetpackStatus,
		  } )
		: identity
)( ProfileWizard );
