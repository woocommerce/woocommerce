/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment, useEffect, useRef } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import { SectionHeader, ScrollTo } from '@woocommerce/components';
import { useSettings } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './index.scss';
import { config } from './config';
import Setting from './setting';
import HistoricalData from './historical-data';

const Settings = ( { createNotice, query } ) => {
	const {
		settingsError,
		isRequesting,
		isDirty,
		persistSettings,
		updateAndPersistSettings,
		updateSettings,
		wcAdminSettings,
	} = useSettings( 'wc_admin', [ 'wcAdminSettings' ] );
	const hasSaved = useRef( false );

	useEffect( () => {
		function warnIfUnsavedChanges( event ) {
			if ( isDirty ) {
				event.returnValue = __(
					'You have unsaved changes. If you proceed, they will be lost.',
					'woocommerce'
				);
				return event.returnValue;
			}
		}
		window.addEventListener( 'beforeunload', warnIfUnsavedChanges );
		return () =>
			window.removeEventListener( 'beforeunload', warnIfUnsavedChanges );
	}, [ isDirty ] );

	useEffect( () => {
		if ( isRequesting ) {
			hasSaved.current = true;
			return;
		}
		if ( ! isRequesting && hasSaved.current ) {
			if ( ! settingsError ) {
				createNotice(
					'success',
					__(
						'Your settings have been successfully saved.',
						'woocommerce'
					)
				);
			} else {
				createNotice(
					'error',
					__(
						'There was an error saving your settings. Please try again.',
						'woocommerce'
					)
				);
			}
			hasSaved.current = false;
		}
	}, [ isRequesting, settingsError, createNotice ] );

	const resetDefaults = () => {
		if (
			// eslint-disable-next-line no-alert
			window.confirm(
				__(
					'Are you sure you want to reset all settings to default values?',
					'woocommerce'
				)
			)
		) {
			const resetSettings = Object.keys( config ).reduce(
				( result, setting ) => {
					result[ setting ] = config[ setting ].defaultValue;
					return result;
				},
				{}
			);

			updateAndPersistSettings( 'wcAdminSettings', resetSettings );
			recordEvent( 'analytics_settings_reset_defaults' );
		}
	};

	const saveChanges = () => {
		persistSettings();
		recordEvent( 'analytics_settings_save', wcAdminSettings );

		// On save, reset persisted query properties of Nav Menu links to default
		query.period = undefined;
		query.compare = undefined;
		query.before = undefined;
		query.after = undefined;
		query.interval = undefined;
		query.type = undefined;
		window.wpNavMenuUrlUpdate( query );
	};

	const handleInputChange = ( e ) => {
		const { checked, name, type, value } = e.target;
		const nextSettings = { ...wcAdminSettings };

		if ( type === 'checkbox' ) {
			if ( checked ) {
				nextSettings[ name ] = [ ...nextSettings[ name ], value ];
			} else {
				nextSettings[ name ] = nextSettings[ name ].filter(
					( v ) => v !== value
				);
			}
		} else {
			nextSettings[ name ] = value;
		}
		updateSettings( 'wcAdminSettings', nextSettings );
	};

	return (
		<Fragment>
			<SectionHeader
				title={ __( 'Analytics settings', 'woocommerce' ) }
			/>
			<div className="woocommerce-settings__wrapper">
				{ Object.keys( config ).map( ( setting ) => (
					<Setting
						handleChange={ handleInputChange }
						value={ wcAdminSettings[ setting ] }
						key={ setting }
						name={ setting }
						{ ...config[ setting ] }
					/>
				) ) }
				<div className="woocommerce-settings__actions">
					<Button isSecondary onClick={ resetDefaults }>
						{ __( 'Reset defaults', 'woocommerce' ) }
					</Button>
					<Button
						isPrimary
						isBusy={ isRequesting }
						onClick={ saveChanges }
					>
						{ __( 'Save settings', 'woocommerce' ) }
					</Button>
				</div>
			</div>
			{ query.import === 'true' ? (
				<ScrollTo offset="-56">
					<HistoricalData createNotice={ createNotice } />
				</ScrollTo>
			) : (
				<HistoricalData createNotice={ createNotice } />
			) }
		</Fragment>
	);
};

export default compose(
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
		};
	} )
)( Settings );
