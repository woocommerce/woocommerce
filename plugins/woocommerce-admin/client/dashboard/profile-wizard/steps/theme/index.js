/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from 'newspack-components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import Gridicon from 'gridicons';
import { TabPanel, Tooltip } from '@wordpress/components';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, H } from '@woocommerce/components';

/**
 * Internal depdencies
 */
import withSelect from 'wc-api/with-select';
import './style.scss';
import { recordEvent } from 'lib/tracks';

class Theme extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			activeTab: 'all',
		};

		this.onChoose = this.onChoose.bind( this );
		this.onSelectTab = this.onSelectTab.bind( this );
		this.openDemo = this.openDemo.bind( this );
	}

	async onChoose( theme ) {
		const { addNotice, goToNextStep, isError, updateProfileItems } = this.props;

		recordEvent( 'storeprofiler_store_theme_choose', { theme } );
		await updateProfileItems( { theme } );

		if ( ! isError ) {
			// @todo This should send profile information to woocommerce.com.
			goToNextStep();
		} else {
			addNotice( {
				status: 'error',
				message: __( 'There was a problem selecting your store theme.', 'woocommerce-admin' ),
			} );
		}
	}

	openDemo( theme ) {
		// @todo This should open a theme demo preview.

		recordEvent( 'storeprofiler_store_theme_live_demo', { theme } );
	}

	renderTheme( theme ) {
		const { demo_url, has_woocommerce_support, image, slug, title } = theme;

		return (
			<Card className="woocommerce-profile-wizard__theme" key={ theme.slug }>
				{ image && (
					<img alt={ title } src={ image } className="woocommerce-profile-wizard__theme-image" />
				) }
				<div className="woocommerce-profile-wizard__theme-details">
					<H className="woocommerce-profile-wizard__theme-name">
						{ title }
						{ ! has_woocommerce_support && (
							<Tooltip
								text={ __( 'This theme does not support WooCommerce.', 'woocommerce-admin' ) }
							>
								<span>
									<Gridicon icon="info" role="img" aria-hidden="true" focusable="false" />
								</span>
							</Tooltip>
						) }
					</H>
					<p className="woocommerce-profile-wizard__theme-status">
						{ this.getThemeStatus( theme ) }
					</p>
					<div className="woocommerce-profile-wizard__theme-actions">
						<Button
							isPrimary={ Boolean( demo_url ) }
							isDefault={ ! Boolean( demo_url ) }
							onClick={ () => this.onChoose( slug ) }
						>
							{ __( 'Choose', 'woocommerce-admin' ) }
						</Button>
						{ demo_url && (
							<Button isDefault onClick={ () => this.openDemo( slug ) }>
								{ __( 'Live Demo', 'woocommerce-admin' ) }
							</Button>
						) }
					</div>
				</div>
			</Card>
		);
	}

	getThemeStatus( theme ) {
		const { installed, price, slug } = theme;
		const { activeTheme } = wcSettings.onboarding;

		if ( activeTheme === slug ) {
			return __( 'Currently active theme', 'woocommerce-admin' );
		}
		if ( installed ) {
			return __( 'Installed', 'woocommerce-admin' );
		} else if ( this.getPriceValue( price ) <= 0 ) {
			return __( 'Free', 'woocommerce-admin' );
		}

		return sprintf( __( '%s per year', 'woocommerce-admin' ), decodeEntities( price ) );
	}

	onSelectTab( tab ) {
		recordEvent( 'storeprofiler_store_theme_navigate', { navigation: tab } );
		this.setState( { activeTab: tab } );
	}

	getPriceValue( string ) {
		return Number( decodeEntities( string ).replace( /[^0-9.-]+/g, '' ) );
	}

	getThemes() {
		const { themes } = wcSettings.onboarding;
		const { activeTab } = this.state;

		switch ( activeTab ) {
			case 'paid':
				return themes.filter( theme => this.getPriceValue( theme.price ) > 0 );
			case 'free':
				return themes.filter( theme => this.getPriceValue( theme.price ) <= 0 );
			case 'all':
			default:
				return themes;
		}
	}

	render() {
		const themes = this.getThemes();

		return (
			<Fragment>
				<H className="woocommerce-profile-wizard__header-title">
					{ __( 'Choose a theme', 'woocommerce-admin' ) }
				</H>
				<H className="woocommerce-profile-wizard__header-subtitle">
					{ __( 'Your theme determines how your store appears to customers', 'woocommerce-admin' ) }
				</H>
				<TabPanel
					className="woocommerce-profile-wizard__themes-tab-panel"
					activeClass="is-active"
					onSelect={ this.onSelectTab }
					tabs={ [
						{
							name: 'all',
							title: __( 'All themes', 'woocommerce-admin' ),
						},
						{
							name: 'paid',
							title: __( 'Paid themes', 'woocommerce-admin' ),
						},
						{
							name: 'free',
							title: __( 'Free themes', 'woocommerce-admin' ),
						},
					] }
				>
					{ () => (
						<div className="woocommerce-profile-wizard__themes">
							{ themes && themes.map( theme => this.renderTheme( theme ) ) }
						</div>
					) }
				</TabPanel>
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
		const { addNotice, updateProfileItems } = dispatch( 'wc-api' );

		return {
			addNotice,
			updateProfileItems,
		};
	} )
)( Theme );
