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

const SETTINGS_FILTER = 'woocommerce_admin_analytics_settings';

class Settings extends Component {
	constructor() {
		super( ...arguments );

		const settings = {};
		analyticsSettings.forEach( setting => ( settings[ setting.name ] = setting.initialValue ) );

		this.state = {
			settings: settings,
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
				__( 'Are you sure you want to reset all settings to default values?', 'wc-admin' )
			)
		) {
			const settings = {};
			analyticsSettings.forEach( setting => ( settings[ setting.name ] = setting.defaultValue ) );
			this.setState( { settings }, this.saveChanges );
		}
	};

	saveChanges = () => {
		this.props.updateSettings( this.state.settings );
		// @todo Need a confirmation on successful update.
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
		const { hasError } = this.state;
		if ( hasError ) {
			return null;
		}

		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics/revenue', __( 'Analytics', 'wc-admin' ) ],
						__( 'Settings', 'wc-admin' ),
					] }
				/>
				<SectionHeader title={ __( 'Analytics Settings', 'wc-admin' ) } />
				<div className="woocommerce-settings__wrapper">
					{ analyticsSettings.map( setting => (
						<Setting
							handleChange={ this.handleInputChange }
							helpText={ setting.helpText }
							inputType={ setting.inputType }
							key={ setting.name }
							label={ setting.label }
							name={ setting.name }
							options={ setting.options }
							value={ this.state.settings[ setting.name ] }
						/>
					) ) }
					<div className="woocommerce-settings__actions">
						<Button isDefault onClick={ this.resetDefaults }>
							{ __( 'Reset Defaults', 'wc-admin' ) }
						</Button>
						<Button isPrimary onClick={ this.saveChanges }>
							{ __( 'Save Changes', 'wc-admin' ) }
						</Button>
					</div>
				</div>
			</Fragment>
		);
	}
}

export default compose(
	withDispatch( dispatch => {
		const { updateSettings } = dispatch( 'wc-api' );

		return {
			updateSettings,
		};
	} )
)( useFilters( SETTINGS_FILTER )( Settings ) );
