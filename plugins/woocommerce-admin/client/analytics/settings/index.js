/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { remove } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { SectionHeader, useFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './index.scss';
import { analyticsSettings } from './config';
import Header from 'header';
import Setting from './setting';
import HistoricalData from './historical-data';
import withSelect from 'wc-api/with-select';

const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';

class Settings extends Component {
	constructor() {
		super( ...arguments );

		const settings = {};
		analyticsSettings.forEach( setting => ( settings[ setting.name ] = setting.initialValue ) );

		this.state = {
			settings: settings,
			saving: false,
		};

		this.handleInputChange = this.handleInputChange.bind( this );
	}

	componentDidCatch( error ) {
		this.setState( {
			hasError: true,
		} );
		/* eslint-disable no-console */
		console.warn( error );
		/* eslint-enable no-console */
	}

	resetDefaults = () => {
		if (
			window.confirm(
				__( 'Are you sure you want to reset all settings to default values?', 'woocommerce-admin' )
			)
		) {
			const settings = {};
			analyticsSettings.forEach( setting => ( settings[ setting.name ] = setting.defaultValue ) );
			this.setState( { settings }, this.saveChanges );
		}
	};

	componentDidUpdate() {
		const { addNotice, isError, isRequesting } = this.props;
		const { saving } = this.state;

		if ( saving && ! isRequesting ) {
			if ( ! isError ) {
				addNotice( {
					status: 'success',
					message: __( 'Your settings have been successfully saved.', 'woocommerce-admin' ),
				} );
			} else {
				addNotice( {
					status: 'error',
					message: __(
						'There was an error saving your settings.  Please try again.',
						'woocommerce-admin'
					),
				} );
			}
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { saving: false } );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	saveChanges = () => {
		this.props.updateSettings( this.state.settings );
		this.setState( { saving: true } );
	};

	handleInputChange( e ) {
		const { checked, name, type, value } = e.target;
		const { settings } = this.state;

		if ( 'checkbox' === type ) {
			if ( checked ) {
				settings[ name ].push( value );
			} else {
				remove( settings[ name ], v => v === value );
			}
		} else {
			settings[ name ] = value;
		}

		this.setState( { settings } );
	}

	render() {
		const { addNotice } = this.props;
		const { hasError } = this.state;
		if ( hasError ) {
			return null;
		}

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics/revenue', __( 'Analytics', 'woocommerce-admin' ) ],
						__( 'Settings', 'woocommerce-admin' ),
					] }
				/>
				<SectionHeader title={ __( 'Analytics Settings', 'woocommerce-admin' ) } />
				<div className="woocommerce-settings__wrapper">
					{ analyticsSettings.map( setting => (
						<Setting
							handleChange={ this.handleInputChange }
							value={ this.state.settings[ setting.name ] }
							key={ setting.name }
							{ ...setting }
						/>
					) ) }
					<div className="woocommerce-settings__actions">
						<Button isDefault onClick={ this.resetDefaults }>
							{ __( 'Reset Defaults', 'woocommerce-admin' ) }
						</Button>
						<Button isPrimary onClick={ this.saveChanges }>
							{ __( 'Save Changes', 'woocommerce-admin' ) }
						</Button>
					</div>
					<HistoricalData addNotice={ addNotice } />
				</div>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getSettings, getSettingsError, isGetSettingsRequesting } = select( 'wc-api' );

		const settings = getSettings();
		const isError = Boolean( getSettingsError() );
		const isRequesting = isGetSettingsRequesting();

		return { getSettings, isError, isRequesting, settings };
	} ),
	withDispatch( dispatch => {
		const { addNotice } = dispatch( 'wc-admin' );
		const { updateSettings } = dispatch( 'wc-api' );

		return {
			addNotice,
			updateSettings,
		};
	} )
)( useFilters( SETTINGS_FILTER )( Settings ) );
