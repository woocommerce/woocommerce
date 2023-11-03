/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import {
	IMPORT_STORE_NAME,
	NOTES_STORE_NAME,
	QUERY_DEFAULTS,
	SECOND,
} from '@woocommerce/data';
import { withDispatch, withSelect } from '@wordpress/data';
import { withSpokenMessages } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { formatParams } from './utils';
import HistoricalDataLayout from './layout';

class HistoricalData extends Component {
	constructor() {
		super( ...arguments );

		this.dateFormat = __( 'MM/DD/YYYY', 'woocommerce' );
		this.intervalId = -1;
		this.lastImportStopTimestamp = 0;
		this.cacheNeedsClearing = true;

		this.onImportFinished = this.onImportFinished.bind( this );
		this.onImportStarted = this.onImportStarted.bind( this );
		this.clearStatusAndTotalsCache =
			this.clearStatusAndTotalsCache.bind( this );
		this.stopImport = this.stopImport.bind( this );
		this.startStatusCheckInterval =
			this.startStatusCheckInterval.bind( this );
		this.cancelStatusCheckInterval =
			this.cancelStatusCheckInterval.bind( this );
	}

	startStatusCheckInterval() {
		if ( this.intervalId < 0 ) {
			this.cacheNeedsClearing = true;
			this.intervalId = setInterval( () => {
				this.clearCache( 'getImportStatus' );
			}, 3 * SECOND );
		}
	}

	cancelStatusCheckInterval() {
		clearInterval( this.intervalId );
		this.intervalId = -1;
	}

	clearCache( resolver, query ) {
		const { invalidateResolution, lastImportStartTimestamp } = this.props;
		const preparedQuery =
			resolver === 'getImportStatus' ? lastImportStartTimestamp : query;
		invalidateResolution( resolver, [ preparedQuery ] ).then( () => {
			this.cacheNeedsClearing = false;
		} );
	}

	stopImport() {
		this.cancelStatusCheckInterval();
		this.lastImportStopTimestamp = Date.now();
	}

	onImportFinished() {
		const { debouncedSpeak } = this.props;
		if ( ! this.cacheNeedsClearing ) {
			debouncedSpeak( 'Import complete' );
			this.stopImport();
		}
	}

	onImportStarted() {
		const { notes, setImportStarted, updateNote } = this.props;

		const historicalDataNote = notes.find(
			( note ) => note.name === 'wc-admin-historical-data'
		);
		if ( historicalDataNote ) {
			updateNote( historicalDataNote.id, { status: 'actioned' } );
		}

		setImportStarted( true );
	}

	clearStatusAndTotalsCache() {
		const { selectedPeriod, skipChecked } = this.props;
		const params = formatParams(
			this.dateFormat,
			selectedPeriod,
			skipChecked
		);

		this.clearCache( 'getImportTotals', params );
		this.clearCache( 'getImportStatus' );
	}

	isImportationInProgress() {
		const { lastImportStartTimestamp } = this.props;
		return (
			( typeof lastImportStartTimestamp !== 'undefined' &&
				typeof this.lastImportStopTimestamp === 'undefined' ) ||
			lastImportStartTimestamp > this.lastImportStopTimestamp
		);
	}

	render() {
		const {
			activeImport,
			createNotice,
			lastImportStartTimestamp,
			selectedPeriod,
			skipChecked,
		} = this.props;

		return (
			<HistoricalDataLayout
				activeImport={ activeImport }
				cacheNeedsClearing={ this.cacheNeedsClearing }
				createNotice={ createNotice }
				dateFormat={ this.dateFormat }
				inProgress={ this.isImportationInProgress() }
				onImportFinished={ this.onImportFinished }
				onImportStarted={ this.onImportStarted }
				lastImportStartTimestamp={ lastImportStartTimestamp }
				clearStatusAndTotalsCache={ this.clearStatusAndTotalsCache }
				period={ selectedPeriod }
				skipChecked={ skipChecked }
				startStatusCheckInterval={ this.startStatusCheckInterval }
				stopImport={ this.stopImport }
			/>
		);
	}
}

export default compose( [
	withSelect( ( select ) => {
		const { getNotes } = select( NOTES_STORE_NAME );
		const { getImportStarted, getFormSettings } =
			select( IMPORT_STORE_NAME );

		const notesQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'update',
			status: 'unactioned',
		};
		const notes = getNotes( notesQuery );
		const { activeImport, lastImportStartTimestamp } = getImportStarted();
		const { period: selectedPeriod, skipPrevious: skipChecked } =
			getFormSettings();

		return {
			activeImport,
			lastImportStartTimestamp,
			notes,
			selectedPeriod,
			skipChecked,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateNote } = dispatch( NOTES_STORE_NAME );
		const { invalidateResolution, setImportStarted } =
			dispatch( IMPORT_STORE_NAME );

		return {
			invalidateResolution,
			setImportStarted,
			updateNote,
		};
	} ),
	withSpokenMessages,
] )( HistoricalData );
