/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { Button } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { IMPORT_STORE_NAME } from '@woocommerce/data';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { formatParams } from './utils';

function HistoricalDataActions( {
	clearStatusAndTotalsCache,
	createNotice,
	dateFormat,
	importDate,
	onImportStarted,
	selectedPeriod,
	stopImport,
	skipChecked,
	status,
	setImportStarted,
	updateImportation,
} ) {
	const makeQuery = ( path, errorMessage, importStarted = false ) => {
		updateImportation( path, importStarted )
			.then( ( response ) => {
				if ( response.status === 'success' ) {
					createNotice( 'success', response.message );
				} else {
					createNotice( 'error', errorMessage );
					setImportStarted( false );
					stopImport();
				}
			} )
			.catch( ( error ) => {
				if ( error && error.message ) {
					createNotice( 'error', error.message );
					setImportStarted( false );
					stopImport();
				}
			} );
	};

	const onStartImport = () => {
		const path = addQueryArgs(
			'/wc-analytics/reports/import',
			formatParams( dateFormat, selectedPeriod, skipChecked )
		);
		const errorMessage = __(
			'There was a problem rebuilding your report data.',
			'woocommerce'
		);

		const importStarted = true;
		makeQuery( path, errorMessage, importStarted );
		onImportStarted();
	};

	const onStopImport = () => {
		stopImport();
		const path = '/wc-analytics/reports/import/cancel';
		const errorMessage = __(
			'There was a problem stopping your current import.',
			'woocommerce'
		);
		makeQuery( path, errorMessage );
	};

	const deletePreviousData = () => {
		const path = '/wc-analytics/reports/import/delete';
		const errorMessage = __(
			'There was a problem deleting your previous data.',
			'woocommerce'
		);
		makeQuery( path, errorMessage );

		recordEvent( 'analytics_import_delete_previous' );

		setImportStarted( false );
	};
	const reimportData = () => {
		setImportStarted( false );
		// We need to clear the cache of the selectors `getImportTotals` and `getImportStatus`
		clearStatusAndTotalsCache();
	};
	const getActions = () => {
		const importDisabled = status !== 'ready';

		// An import is currently in progress
		if (
			[ 'initializing', 'customers', 'orders', 'finalizing' ].includes(
				status
			)
		) {
			return (
				<Fragment>
					<Button
						className="woocommerce-settings-historical-data__action-button"
						isPrimary
						onClick={ onStopImport }
					>
						{ __( 'Stop Import', 'woocommerce' ) }
					</Button>
					<div className="woocommerce-setting__help woocommerce-settings-historical-data__action-help">
						{ __(
							'Imported data will not be lost if the import is stopped.',
							'woocommerce'
						) }
						<br />
						{ __(
							'Navigating away from this page will not affect the import.',
							'woocommerce'
						) }
					</div>
				</Fragment>
			);
		}

		if ( [ 'ready', 'nothing' ].includes( status ) ) {
			if ( importDate ) {
				return (
					<Fragment>
						<Button
							isPrimary
							onClick={ onStartImport }
							disabled={ importDisabled }
						>
							{ __( 'Start', 'woocommerce' ) }
						</Button>
						<Button isSecondary onClick={ deletePreviousData }>
							{ __(
								'Delete Previously Imported Data',
								'woocommerce'
							) }
						</Button>
					</Fragment>
				);
			}

			return (
				<Fragment>
					<Button
						isPrimary
						onClick={ onStartImport }
						disabled={ importDisabled }
					>
						{ __( 'Start', 'woocommerce' ) }
					</Button>
				</Fragment>
			);
		}

		if ( status === 'error' ) {
			createNotice(
				'error',
				__(
					'Something went wrong with the importation process.',
					'woocommerce'
				)
			);
		}

		// Has imported all possible data
		return (
			<Fragment>
				<Button isSecondary onClick={ reimportData }>
					{ __( 'Re-import Data', 'woocommerce' ) }
				</Button>
				<Button isSecondary onClick={ deletePreviousData }>
					{ __( 'Delete Previously Imported Data', 'woocommerce' ) }
				</Button>
			</Fragment>
		);
	};

	return (
		<div className="woocommerce-settings__actions woocommerce-settings-historical-data__actions">
			{ getActions() }
		</div>
	);
}

export default compose( [
	withSelect( ( select ) => {
		const { getFormSettings } = select( IMPORT_STORE_NAME );

		const { period: selectedPeriod, skipPrevious: skipChecked } =
			getFormSettings();

		return {
			selectedPeriod,
			skipChecked,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateImportation, setImportStarted } =
			dispatch( IMPORT_STORE_NAME );
		const { createNotice } = dispatch( 'core/notices' );
		return {
			createNotice,
			setImportStarted,
			updateImportation,
		};
	} ),
] )( HistoricalDataActions );
