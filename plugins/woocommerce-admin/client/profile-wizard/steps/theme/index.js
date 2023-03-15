/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import InfoIcon from 'gridicons/dist/info';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	TabPanel,
	Tooltip,
} from '@wordpress/components';
import { withDispatch, withSelect } from '@wordpress/data';
import { H } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './style.scss';
import ThemeUploader from './uploader';
import ThemePreview from './preview';
import { getPriceValue } from '../../../dashboard/utils';
import { getAdminSetting, setAdminSetting } from '~/utils/admin-settings';

class Theme extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			activeTab: 'all',
			chosen: null,
			demo: null,
			uploadedThemes: [],
		};

		this.handleUploadComplete = this.handleUploadComplete.bind( this );
		this.onChoose = this.onChoose.bind( this );
		this.onClosePreview = this.onClosePreview.bind( this );
		this.onSelectTab = this.onSelectTab.bind( this );
		this.openDemo = this.openDemo.bind( this );
		this.skipStep = this.skipStep.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { isError, isUpdatingProfileItems, createNotice } = this.props;
		const { chosen } = this.state;
		const isRequestSuccessful =
			! isUpdatingProfileItems &&
			prevProps.isUpdatingProfileItems &&
			! isError &&
			chosen;
		const isRequestError =
			! isUpdatingProfileItems && prevProps.isRequesting && isError;

		if ( isRequestSuccessful ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { chosen: null } );
			/* eslint-enable react/no-did-update-set-state */
			this.props.goToNextStep();
		}

		if ( isRequestError ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { chosen: null } );
			/* eslint-enable react/no-did-update-set-state */
			createNotice(
				'error',
				__(
					'There was a problem selecting your store theme',
					'woocommerce'
				)
			);
		}
	}

	skipStep() {
		const { activeTheme = '' } = getAdminSetting( 'onboarding', {} );
		recordEvent( 'storeprofiler_store_theme_skip_step', {
			active_theme: activeTheme,
		} );
		this.props.goToNextStep();
	}

	onChoose( theme, location = '' ) {
		const { updateProfileItems } = this.props;
		const { is_installed: isInstalled, price, slug } = theme;
		const { activeTheme = '' } = getAdminSetting( 'onboarding', {} );

		this.setState( { chosen: slug } );
		recordEvent( 'storeprofiler_store_theme_choose', {
			theme: slug,
			location,
		} );

		if ( slug !== activeTheme && isInstalled ) {
			this.activateTheme( slug );
		} else if ( slug !== activeTheme && getPriceValue( price ) <= 0 ) {
			this.installTheme( slug );
		} else {
			updateProfileItems( { theme: slug } );
		}
	}

	installTheme( slug ) {
		const { createNotice } = this.props;

		apiFetch( {
			path: '/wc-admin/onboarding/themes/install?theme=' + slug,
			method: 'POST',
		} )
			.then( () => {
				this.activateTheme( slug );
			} )
			.catch( ( response ) => {
				this.setState( { chosen: null } );
				createNotice( 'error', response.message );
			} );
	}

	activateTheme( slug ) {
		const { createNotice, updateProfileItems } = this.props;

		apiFetch( {
			path: '/wc-admin/onboarding/themes/activate?theme=' + slug,
			method: 'POST',
		} )
			.then( ( response ) => {
				createNotice(
					'success',
					sprintf(
						/* translators: The name of the theme that was installed and activated */
						__(
							'%s was installed and activated on your site',
							'woocommerce'
						),
						response.name
					)
				);
				setAdminSetting( 'onboarding', {
					...getAdminSetting( 'onboarding', {} ),
					activeTheme: response.slug,
				} );
				updateProfileItems( { theme: slug } );
			} )
			.catch( ( response ) => {
				this.setState( { chosen: null } );
				createNotice( 'error', response.message );
			} );
	}

	onClosePreview() {
		const { demo } = this.state;
		recordEvent( 'storeprofiler_store_theme_demo_close', {
			theme: demo.slug,
		} );
		document.body.classList.remove( 'woocommerce-theme-preview-active' );
		this.setState( { demo: null } );
	}

	openDemo( theme ) {
		recordEvent( 'storeprofiler_store_theme_live_demo', {
			theme: theme.slug,
		} );
		document.body.classList.add( 'woocommerce-theme-preview-active' );
		this.setState( { demo: theme } );
	}

	renderTheme( theme ) {
		const {
			demo_url: demoUrl,
			has_woocommerce_support: hasSupport,
			image,
			slug,
			title,
		} = theme;
		const { chosen } = this.state;
		const { activeTheme = '' } = getAdminSetting( 'onboarding', {} );

		return (
			<Card className="woocommerce-profile-wizard__theme" key={ slug }>
				<CardBody size={ null }>
					{ image && (
						<div
							className="woocommerce-profile-wizard__theme-image"
							style={ { backgroundImage: `url(${ image })` } }
							role="img"
							aria-label={ title }
						/>
					) }
				</CardBody>
				<CardBody className="woocommerce-profile-wizard__theme-details">
					<H className="woocommerce-profile-wizard__theme-name">
						{ title }
						{ ! hasSupport && (
							<Tooltip
								text={ __(
									'This theme does not support WooCommerce.',
									'woocommerce'
								) }
							>
								<span>
									<InfoIcon
										role="img"
										aria-hidden="true"
										focusable="false"
									/>
								</span>
							</Tooltip>
						) }
					</H>
					<p className="woocommerce-profile-wizard__theme-status">
						{ this.getThemeStatus( theme ) }
					</p>
				</CardBody>
				<CardFooter>
					{ slug === activeTheme ? (
						<Button
							isPrimary
							onClick={ () => this.onChoose( theme, 'card' ) }
							isBusy={ chosen === slug }
							disabled={ chosen === slug }
						>
							{ __(
								'Continue with my active theme',
								'woocommerce'
							) }
						</Button>
					) : (
						<Button
							isSecondary
							onClick={ () => this.onChoose( theme, 'card' ) }
							isBusy={ chosen === slug }
							disabled={ chosen === slug }
						>
							{ __( 'Choose', 'woocommerce' ) }
						</Button>
					) }
					{ demoUrl && (
						<Button
							isTertiary
							onClick={ () => this.openDemo( theme ) }
						>
							{ __( 'Live demo', 'woocommerce' ) }
						</Button>
					) }
				</CardFooter>
			</Card>
		);
	}

	getThemeStatus( theme ) {
		const { is_installed: isInstalled, price, slug } = theme;
		const { activeTheme = '' } = getAdminSetting( 'onboarding', {} );

		if ( activeTheme === slug ) {
			return __( 'Currently active theme', 'woocommerce' );
		}
		if ( isInstalled ) {
			return __( 'Installed', 'woocommerce' );
		} else if ( getPriceValue( price ) <= 0 ) {
			return __( 'Free', 'woocommerce' );
		}

		return sprintf(
			__( '%s per year', 'woocommerce' ),
			decodeEntities( price )
		);
	}

	doesActiveThemeSupportWooCommerce() {
		const { activeTheme = '' } = getAdminSetting( 'onboarding', {} );
		const allThemes = this.getThemes();
		const currentTheme = allThemes.find(
			( theme ) => theme.slug === activeTheme
		);
		return currentTheme && currentTheme.has_woocommerce_support;
	}

	onSelectTab( tab ) {
		recordEvent( 'storeprofiler_store_theme_navigate', {
			navigation: tab,
		} );
		this.setState( { activeTab: tab } );
	}

	getPriceValue( string ) {
		return Number( decodeEntities( string ).replace( /[^0-9.-]+/g, '' ) );
	}

	getThemes( activeTab = 'all' ) {
		const { uploadedThemes } = this.state;
		const { activeTheme = '', themes = [] } = getAdminSetting(
			'onboarding',
			{}
		);
		const allThemes = [
			...themes.filter(
				( theme ) =>
					theme &&
					( theme.has_woocommerce_support ||
						theme.slug === activeTheme )
			),
			...uploadedThemes,
		];

		switch ( activeTab ) {
			case 'paid':
				return allThemes.filter(
					( theme ) => getPriceValue( theme.price ) > 0
				);
			case 'free':
				return allThemes.filter(
					( theme ) => getPriceValue( theme.price ) <= 0
				);
			case 'all':
			default:
				return allThemes;
		}
	}

	handleUploadComplete( upload ) {
		if ( upload.status === 'success' && upload.theme_data ) {
			this.setState( {
				uploadedThemes: [
					...this.state.uploadedThemes,
					upload.theme_data,
				],
			} );

			recordEvent( 'storeprofiler_store_theme_upload', {
				theme: upload.theme_data.slug,
			} );
		}
	}

	render() {
		const { activeTab, chosen, demo } = this.state;
		const themes = this.getThemes( activeTab );
		const activeThemeSupportsWooCommerce =
			this.doesActiveThemeSupportWooCommerce();

		return (
			<Fragment>
				<div className="woocommerce-profile-wizard__step-header">
					<Text
						variant="title.small"
						as="h2"
						size="20"
						lineHeight="28px"
					>
						{ __( 'Choose a theme', 'woocommerce' ) }
					</Text>
					<Text variant="body" as="p">
						{ __(
							"Choose how your store appears to customers. And don't worry, you can always switch themes and edit them later.",
							'woocommerce'
						) }
					</Text>
				</div>
				<TabPanel
					className="woocommerce-profile-wizard__themes-tab-panel"
					activeClass="is-active"
					onSelect={ this.onSelectTab }
					tabs={ [
						{
							name: 'all',
							title: __( 'All themes', 'woocommerce' ),
						},
						{
							name: 'paid',
							title: __( 'Paid themes', 'woocommerce' ),
						},
						{
							name: 'free',
							title: __( 'Free themes', 'woocommerce' ),
						},
					] }
				>
					{ () => (
						<div className="woocommerce-profile-wizard__themes">
							{ themes &&
								themes.map( ( theme ) =>
									this.renderTheme( theme )
								) }
							<ThemeUploader
								onUploadComplete={ this.handleUploadComplete }
							/>
						</div>
					) }
				</TabPanel>
				{ demo && (
					<ThemePreview
						theme={ demo }
						onChoose={ () => this.onChoose( demo, 'card' ) }
						onClose={ this.onClosePreview }
						isBusy={ chosen === demo.slug }
					/>
				) }
				{ activeThemeSupportsWooCommerce && (
					<p className="woocommerce-profile-wizard__themes-skip-this-step">
						<Button
							isLink
							className="woocommerce-profile-wizard__skip"
							onClick={ () => this.skipStep() }
						>
							{ __( 'Skip this step', 'woocommerce' ) }
						</Button>
					</p>
				) }
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getOnboardingError, isOnboardingRequesting } =
			select( ONBOARDING_STORE_NAME );

		return {
			isError: Boolean( getOnboardingError( 'updateProfileItems' ) ),
			isUpdatingProfileItems:
				isOnboardingRequesting( 'updateProfileItems' ),
			profileItems: getProfileItems(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateProfileItems } = dispatch( ONBOARDING_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateProfileItems,
		};
	} )
)( Theme );
