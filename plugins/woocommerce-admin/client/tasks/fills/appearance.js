/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Card, CardBody } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { filter } from 'lodash';
import { withDispatch, withSelect } from '@wordpress/data';

import { Stepper, TextControl, ImageUpload } from '@woocommerce/components';
import {
	OPTIONS_STORE_NAME,
	ONBOARDING_STORE_NAME,
	WC_ADMIN_NAMESPACE,
} from '@woocommerce/data';
import { queueRecordEvent, recordEvent } from '@woocommerce/tracks';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
class Appearance extends Component {
	constructor( props ) {
		super( props );
		const { hasHomepage, hasProducts } = props.task.additionalData;

		this.stepVisibility = {
			homepage: ! hasHomepage,
			import: ! hasProducts,
		};

		this.state = {
			isDirty: false,
			isPending: false,
			logo: null,
			stepIndex: 0,
			isUpdatingLogo: false,
			isUpdatingNotice: false,
			storeNoticeText: props.demoStoreNotice || '',
		};

		this.completeStep = this.completeStep.bind( this );
		this.createHomepage = this.createHomepage.bind( this );
		this.importProducts = this.importProducts.bind( this );
		this.updateLogo = this.updateLogo.bind( this );
		this.updateNotice = this.updateNotice.bind( this );
	}

	componentDidMount() {
		const { themeMods } = this.props.task.additionalData;

		if ( themeMods && themeMods.custom_logo ) {
			/* eslint-disable react/no-did-mount-set-state */
			this.setState( { logo: { id: themeMods.custom_logo } } );
			/* eslint-enable react/no-did-mount-set-state */
		}
	}

	componentDidUpdate( prevProps ) {
		const { isPending, logo } = this.state;
		const { demoStoreNotice } = this.props;

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
			demoStoreNotice &&
			prevProps.demoStoreNotice !== demoStoreNotice
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				storeNoticeText: demoStoreNotice,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	async completeStep() {
		const { stepIndex } = this.state;
		const { actionTask, onComplete } = this.props;
		const nextStep = this.getSteps()[ stepIndex + 1 ];

		if ( nextStep ) {
			this.setState( { stepIndex: stepIndex + 1 } );
		} else {
			this.setState( { isPending: true } );
			await actionTask( 'appearance' );
			onComplete();
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
							'There was an error importing some of the sample products',
							'woocommerce'
						)
					);
				} else {
					createNotice(
						'success',
						__(
							'All sample products have been imported',
							'woocommerce'
						)
					);
				}

				this.setState( { isPending: false } );
				this.completeStep();
			} )
			.catch( ( { message } ) => {
				createNotice(
					'error',
					message ||
						__(
							'There was an error importing the sample products',
							'woocommerce'
						),
					{ __unstableHTML: true }
				);
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
									label: __( 'Customize', 'woocommerce' ),
									onClick: () => {
										queueRecordEvent(
											'tasklist_appearance_customize_homepage',
											{}
										);
										window.location = `${ response.edit_post_link }&wc_onboarding_active_task=appearance`;
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

	async updateLogo() {
		const { createNotice, task, updateOptions } = this.props;
		const { stylesheet, themeMods } = task.additionalData;
		const { logo } = this.state;
		const updatedThemeMods = {
			...themeMods,
			custom_logo: logo ? logo.id : null,
		};

		recordEvent( 'tasklist_appearance_upload_logo' );

		this.setState( { isUpdatingLogo: true } );
		const update = await updateOptions( {
			[ `theme_mods_${ stylesheet }` ]: updatedThemeMods,
		} );

		if ( update.success ) {
			this.setState( { isUpdatingLogo: false } );
			createNotice(
				'success',
				__( 'Store logo updated sucessfully', 'woocommerce' )
			);
			this.completeStep();
		} else {
			createNotice( 'error', update.message );
		}
	}

	async updateNotice() {
		const { createNotice, updateOptions } = this.props;
		const { storeNoticeText } = this.state;

		recordEvent( 'tasklist_appearance_set_store_notice', {
			added_text: Boolean( storeNoticeText.length ),
		} );

		this.setState( { isUpdatingNotice: true } );
		const update = await updateOptions( {
			woocommerce_demo_store: storeNoticeText.length ? 'yes' : 'no',
			woocommerce_demo_store_notice: storeNoticeText,
		} );

		if ( update.success ) {
			this.setState( { isUpdatingNotice: false } );
			createNotice(
				'success',
				__(
					"🎨 Your store is looking great! Don't forget to continue personalizing it",
					'woocommerce'
				)
			);
			this.completeStep();
		} else {
			createNotice( 'error', update.message );
		}
	}

	getSteps() {
		const { isDirty, isPending, logo, storeNoticeText, isUpdatingLogo } =
			this.state;

		const steps = [
			{
				key: 'import',
				label: __( 'Import sample products', 'woocommerce' ),
				description: __(
					'We’ll add some products that will make it easier to see what your store looks like',
					'woocommerce'
				),
				content: (
					<Fragment>
						<Button
							onClick={ this.importProducts }
							isBusy={ isPending }
							isPrimary
						>
							{ __( 'Import products', 'woocommerce' ) }
						</Button>
						<Button onClick={ () => this.completeStep() }>
							{ __( 'Skip', 'woocommerce' ) }
						</Button>
					</Fragment>
				),
				visible: this.stepVisibility.import,
			},
			{
				key: 'homepage',
				label: __( 'Create a custom homepage', 'woocommerce' ),
				description: __(
					'Create a new homepage and customize it to suit your needs',
					'woocommerce'
				),
				content: (
					<Fragment>
						<Button
							isPrimary
							isBusy={ isPending }
							onClick={ this.createHomepage }
						>
							{ __( 'Create homepage', 'woocommerce' ) }
						</Button>
						<Button
							isTertiary
							onClick={ () => {
								recordEvent(
									'tasklist_appearance_create_homepage',
									{ create_homepage: false }
								);
								this.completeStep();
							} }
						>
							{ __( 'Skip', 'woocommerce' ) }
						</Button>
					</Fragment>
				),
				visible: this.stepVisibility.homepage,
			},
			{
				key: 'logo',
				label: __( 'Upload a logo', 'woocommerce' ),
				description: __(
					'Ensure your store is on-brand by adding your logo',
					'woocommerce'
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
							isBusy={ isUpdatingLogo }
							isPrimary
						>
							{ __( 'Proceed', 'woocommerce' ) }
						</Button>
						<Button
							isTertiary
							onClick={ () => this.completeStep() }
						>
							{ __( 'Skip', 'woocommerce' ) }
						</Button>
					</Fragment>
				),
				visible: true,
			},
			{
				key: 'notice',
				label: __( 'Set a store notice', 'woocommerce' ),
				description: __(
					'Optionally display a prominent notice across all pages of your store',
					'woocommerce'
				),
				content: (
					<Fragment>
						<TextControl
							label={ __( 'Store notice text', 'woocommerce' ) }
							placeholder={ __(
								'Store notice text',
								'woocommerce'
							) }
							value={ storeNoticeText }
							onChange={ ( value ) =>
								this.setState( { storeNoticeText: value } )
							}
						/>
						<Button onClick={ this.updateNotice } isPrimary>
							{ __( 'Complete task', 'woocommerce' ) }
						</Button>
					</Fragment>
				),
				visible: true,
			},
		];

		return filter( steps, ( step ) => step.visible );
	}

	render() {
		const { isPending, stepIndex, isUpdatingLogo, isUpdatingNotice } =
			this.state;
		const currentStep = this.getSteps()[ stepIndex ].key;

		return (
			<div className="woocommerce-task-appearance">
				<Card className="woocommerce-task-card">
					<CardBody>
						<Stepper
							isPending={
								isUpdatingNotice || isUpdatingLogo || isPending
							}
							isVertical
							currentStep={ currentStep }
							steps={ this.getSteps() }
						/>
					</CardBody>
				</Card>
			</div>
		);
	}
}

const AppearanceWrapper = compose(
	withSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );

		return {
			demoStoreNotice: getOption( 'woocommerce_demo_store_notice' ),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateOptions } = dispatch( OPTIONS_STORE_NAME );
		const { actionTask } = dispatch( ONBOARDING_STORE_NAME );

		return {
			actionTask,
			createNotice,
			updateOptions,
		};
	} )
)( Appearance );

registerPlugin( 'wc-admin-onboarding-task-appearance', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="appearance">
			{ ( { onComplete, task } ) => (
				<AppearanceWrapper onComplete={ onComplete } task={ task } />
			) }
		</WooOnboardingTask>
	),
} );
