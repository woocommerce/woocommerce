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
			settings,
			saving: false,
			isDirty: false,
		};

		this.handleInputChange = this.handleInputChange.bind( this );
		this.warnIfUnsavedChanges = this.warnIfUnsavedChanges.bind( this );
	}

	componentDidMount() {
		window.addEventListener( 'beforeunload', this.warnIfUnsavedChanges );
	}

	componentWillUnmount() {
		window.removeEventListener( 'beforeunload', this.warnIfUnsavedChanges );
	}

	componentDidCatch( error ) {
		this.setState( {
			hasError: true,
		} );
		/* eslint-disable no-console */
		console.warn( error );
		/* eslint-enable no-console */
	}

	warnIfUnsavedChanges( event ) {
		const { isDirty } = this.state;

		if ( isDirty ) {
			event.returnValue = __(
				'You have unsaved changes. If you proceed, they will be lost.',
				'woocommerce-admin'
			);
			return event.returnValue;
		}
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
		const { createNotice, isError, isRequesting } = this.props;
		const { saving, isDirty } = this.state;
		let newIsDirtyState = isDirty;

		if ( saving && ! isRequesting ) {
			if ( ! isError ) {
				createNotice(
					'success',
					__( 'Your settings have been successfully saved.', 'woocommerce-admin' )
				);
				newIsDirtyState = false;
			} else {
				createNotice(
					'error',
					__( 'There was an error saving your settings.  Please try again.', 'woocommerce-admin' )
				);
			}
			/* eslint-disable react/no-did-update-set-state */
			this.setState( { saving: false, isDirty: newIsDirtyState } );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	/**
	 * Ensure changes are reflected to parameters on the window as well as
	 * the config for construction of this component when re-navigating to
	 * the settings page.
	 *
	 * @param {object} state - State
	 */
	persistChanges( state ) {
		analyticsSettings.forEach( setting => {
			const updatedValue = state.settings[ setting.name ];
			wcSettings.wcAdminSettings[ setting.name ] = updatedValue;
			setting.initialValue = updatedValue;
		} );
	}

	saveChanges = () => {
		this.persistChanges( this.state );
		this.props.updateSettings( { wc_admin: this.state.settings } );

		// TODO: remove this optimistic set of isDirty to false once #2541 is resolved.
		this.setState( { saving: true, isDirty: false } );
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

		this.setState( { settings, isDirty: true } );
	}

	render() {
		const { createNotice } = this.props;
		const { hasError } = this.state;
		if ( hasError ) {
			return null;
		}

		return (
			<Fragment>
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
							{ __( 'Save Settings', 'woocommerce-admin' ) }
						</Button>
					</div>
				</div>
				<HistoricalData createNotice={ createNotice } />
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getSettings, getSettingsError, isGetSettingsRequesting } = select( 'wc-api' );

		const settings = getSettings( 'wc_admin' );
		const isError = Boolean( getSettingsError( 'wc_admin' ) );
		const isRequesting = isGetSettingsRequesting( 'wc_admin' );

		return { getSettings, isError, isRequesting, settings };
	} ),
	withDispatch( dispatch => {
		const { createNotice } = dispatch( 'core/notices' );
		const { updateSettings } = dispatch( 'wc-api' );

		return {
			createNotice,
			updateSettings,
		};
	} )
)( useFilters( SETTINGS_FILTER )( Settings ) );
