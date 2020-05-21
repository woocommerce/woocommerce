/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { difference, filter } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import {
	Card,
	Stepper,
	TextControl,
	ImageUpload,
} from '@woocommerce/components';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { getSetting, setSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import { queueRecordEvent, recordEvent } from 'lib/tracks';
import { WC_ADMIN_NAMESPACE } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';

class Appearance extends Component {
	constructor( props ) {
		super( props );
		const { hasHomepage, hasProducts } = getSetting( 'onboarding', {} );

		this.stepVisibility = {
			homepage: ! hasHomepage,
			import: ! hasProducts,
		};

		this.state = {
			isDirty: false,
			isPending: false,
			logo: null,
			stepIndex: 0,
			storeNoticeText: props.options.woocommerce_demo_store_notice || '',
		};

		this.completeStep = this.completeStep.bind( this );
		this.createHomepage = this.createHomepage.bind( this );
		this.importProducts = this.importProducts.bind( this );
		this.updateLogo = this.updateLogo.bind( this );
		this.updateNotice = this.updateNotice.bind( this );
	}

	componentDidMount() {
		const { themeMods } = getSetting( 'onboarding', {} );

		if ( themeMods.custom_logo ) {
			/* eslint-disable react/no-did-mount-set-state */
			this.setState( { logo: { id: themeMods.custom_logo } } );
			/* eslint-enable react/no-did-mount-set-state */
		}
	}

	async componentDidUpdate( prevProps ) {
		const { isPending, logo, stepIndex } = this.state;
		const {
			createNotice,
			errors,
			hasErrors,
			isRequesting,
			options,
		} = this.props;
		const step = this.getSteps()[ stepIndex ].key;
		const isRequestSuccessful =
			! isRequesting && prevProps.isRequesting && ! hasErrors;

		if ( logo && ! logo.url && ! isPending ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { isPending: true } );
			wp.media
				.attachment( logo.id )
				.fetch()
				.then( () => {
					const logoUrl = wp.media.attachment( logo.id ).get( 'url' );
					this.setState( {
						isPending: false,
						logo: { id: logo.id, url: logoUrl },
					} );
				} );
			/* eslint-enable react/no-did-update-set-state */
		}

		if (
			options.woocommerce_demo_store_notice &&
			prevProps.options.woocommerce_demo_store_notice !==
				options.woocommerce_demo_store_notice
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				storeNoticeText: options.woocommerce_demo_store_notice,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}

		if ( step === 'logo' && isRequestSuccessful ) {
			createNotice(
				'success',
				__( 'Store logo updated sucessfully.', 'woocommerce-admin' )
			);
			this.completeStep();
		}

		if ( step === 'notice' && isRequestSuccessful ) {
			createNotice(
				'success',
				__(
					"🎨 Your store is looking great! Don't forget to continue personalizing it.",
					'woocommerce-admin'
				)
			);
			this.completeStep();
		}

		const newErrors = difference( errors, prevProps.errors );
		newErrors.map( ( error ) => createNotice( 'error', error ) );
	}

	completeStep() {
		const { stepIndex } = this.state;
		const nextStep = this.getSteps()[ stepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { stepIndex: stepIndex + 1 } );
		} else {
			getHistory().push( getNewPath( {}, '/', {} ) );
		}
	}

	importProducts() {
		const { createNotice } = this.props;
		this.setState( { isPending: true } );

		recordEvent( 'tasklist_appearance_import_demo', {} );

		apiFetch( {
			path: `${ WC_ADMIN_NAMESPACE }/onboarding/tasks/import_sample_products`,
			method: 'POST',
		} )
			.then( ( result ) => {
				if ( result.failed && result.failed.length ) {
					createNotice(
						'error',
						__(
							'There was an error importing some of the sample products.',
							'woocommerce-admin'
						)
					);
				} else {
					createNotice(
						'success',
						__(
							'All sample products have been imported.',
							'woocommerce-admin'
						)
					);
					setSetting( 'onboarding', {
						...getSetting( 'onboarding', {} ),
						hasProducts: true,
					} );
				}

				this.setState( { isPending: false } );
				this.completeStep();
			} )
			.catch( ( error ) => {
				createNotice( 'error', error.message );
				this.setState( { isPending: false } );
			} );
	}

	createHomepage() {
		const { createNotice } = this.props;
		this.setState( { isPending: true } );

		recordEvent( 'tasklist_appearance_create_homepage', {
			create_homepage: true,
		} );

		apiFetch( {
			path: '/wc-admin/onboarding/tasks/create_homepage',
			method: 'POST',
		} )
			.then( ( response ) => {
				createNotice( response.status, response.message, {
					actions: response.edit_post_link
						? [
								{
									label: __(
										'Customize',
										'woocommerce-admin'
									),
									onClick: () => {
										queueRecordEvent(
											'tasklist_appearance_customize_homepage',
											{}
										);
										window.location = `${ response.edit_post_link }&wc_onboarding_active_task=homepage`;
									},
								},
						  ]
						: null,
				} );

				this.setState( { isPending: false } );
				this.completeStep();
			} )
			.catch( ( error ) => {
				createNotice( 'error', error.message );
				this.setState( { isPending: false } );
			} );
	}

	updateLogo() {
		const { updateOptions } = this.props;
		const { logo } = this.state;
		const { stylesheet, themeMods } = getSetting( 'onboarding', {} );
		const updatedThemeMods = {
			...themeMods,
			custom_logo: logo ? logo.id : null,
		};

		recordEvent( 'tasklist_appearance_upload_logo' );

		setSetting( 'onboarding', {
			...getSetting( 'onboarding', {} ),
			themeMods: updatedThemeMods,
		} );

		updateOptions( {
			[ `theme_mods_${ stylesheet }` ]: updatedThemeMods,
		} );
	}

	updateNotice() {
		const { updateOptions } = this.props;
		const { storeNoticeText } = this.state;

		recordEvent( 'tasklist_appearance_set_store_notice', {
			added_text: Boolean( storeNoticeText.length ),
		} );

		setSetting( 'onboarding', {
			...getSetting( 'onboarding', {} ),
			isAppearanceComplete: true,
		} );

		updateOptions( {
			woocommerce_task_list_appearance_complete: true,
			woocommerce_demo_store: storeNoticeText.length ? 'yes' : 'no',
			woocommerce_demo_store_notice: storeNoticeText,
		} );
	}

	getSteps() {
		const { isDirty, isPending, logo, storeNoticeText } = this.state;
		const { isRequesting } = this.props;

		const steps = [
			{
				key: 'import',
				label: __( 'Import sample products', 'woocommerce-admin' ),
				description: __(
					'We’ll add some products that will make it easier to see what your store looks like',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<Button
							onClick={ this.importProducts }
							isBusy={ isPending }
							isPrimary
						>
							{ __( 'Import products', 'woocommerce-admin' ) }
						</Button>
						<Button onClick={ () => this.completeStep() }>
							{ __( 'Skip', 'woocommerce-admin' ) }
						</Button>
					</Fragment>
				),
				visible: this.stepVisibility.import,
			},
			{
				key: 'homepage',
				label: __( 'Create a custom homepage', 'woocommerce-admin' ),
				description: __(
					'Create a new homepage and customize it to suit your needs',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<Button
							isPrimary
							isBusy={ isPending }
							onClick={ this.createHomepage }
						>
							{ __( 'Create homepage', 'woocommerce-admin' ) }
						</Button>
						<Button
							onClick={ () => {
								recordEvent(
									'tasklist_appearance_create_homepage',
									{ create_homepage: false }
								);
								this.completeStep();
							} }
						>
							{ __( 'Skip', 'woocommerce-admin' ) }
						</Button>
					</Fragment>
				),
				visible: this.stepVisibility.homepage,
			},
			{
				key: 'logo',
				label: __( 'Upload a logo', 'woocommerce-admin' ),
				description: __(
					'Ensure your store is on-brand by adding your logo',
					'woocommerce-admin'
				),
				content: isPending ? null : (
					<Fragment>
						<ImageUpload
							image={ logo }
							onChange={ ( image ) =>
								this.setState( { isDirty: true, logo: image } )
							}
						/>
						<Button
							disabled={ ! logo && ! isDirty }
							onClick={ this.updateLogo }
							isBusy={ isRequesting }
							isPrimary
						>
							{ __( 'Proceed', 'woocommerce-admin' ) }
						</Button>
						<Button onClick={ () => this.completeStep() }>
							{ __( 'Skip', 'woocommerce-admin' ) }
						</Button>
					</Fragment>
				),
				visible: true,
			},
			{
				key: 'notice',
				label: __( 'Set a store notice', 'woocommerce-admin' ),
				description: __(
					'Optionally display a prominent notice across all pages of your store',
					'woocommerce-admin'
				),
				content: (
					<Fragment>
						<TextControl
							label={ __(
								'Store notice text',
								'woocommerce-admin'
							) }
							placeholder={ __(
								'Store notice text',
								'woocommerce-admin'
							) }
							value={ storeNoticeText }
							onChange={ ( value ) =>
								this.setState( { storeNoticeText: value } )
							}
						/>
						<Button onClick={ this.updateNotice } isPrimary>
							{ __( 'Complete task', 'woocommerce-admin' ) }
						</Button>
					</Fragment>
				),
				visible: true,
			},
		];

		return filter( steps, ( step ) => step.visible );
	}

	render() {
		const { isPending, stepIndex } = this.state;
		const { isRequesting, hasErrors } = this.props;
		const currentStep = this.getSteps()[ stepIndex ].key;

		return (
			<div className="woocommerce-task-appearance">
				<Card className="is-narrow">
					<Stepper
						isPending={
							( isRequesting && ! hasErrors ) || isPending
						}
						isVertical
						currentStep={ currentStep }
						steps={ this.getSteps() }
					/>
				</Card>
			</div>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getOptions,
			getOptionsError,
			isUpdateOptionsRequesting,
		} = select( 'wc-api' );
		const { stylesheet } = getSetting( 'onboarding', {} );

		const options = getOptions( [
			'woocommerce_demo_store',
			'woocommerce_demo_store_notice',
		] );
		const errors = [];
		const uploadLogoError = getOptionsError( [
			`theme_mods_${ stylesheet }`,
		] );
		const storeNoticeError = getOptionsError( [
			'woocommerce_demo_store',
			'woocommerce_demo_store_notice',
		] );
		if ( uploadLogoError ) {
			errors.push( uploadLogoError.message );
		}
		if ( storeNoticeError ) {
			errors.push( storeNoticeError.message );
		}
		const hasErrors = Boolean( errors.length );
		const isRequesting =
			Boolean(
				isUpdateOptionsRequesting( [ `theme_mods_${ stylesheet }` ] )
			) ||
			Boolean(
				isUpdateOptionsRequesting( [
					'woocommerce_task_list_appearance_complete',
					'woocommerce_demo_store',
					'woocommerce_demo_store_notice',
				] )
			);

		return { errors, getOptionsError, hasErrors, isRequesting, options };
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( 'wc-api' );

		return {
			createNotice,
			updateOptions,
		};
	} )
)( Appearance );
