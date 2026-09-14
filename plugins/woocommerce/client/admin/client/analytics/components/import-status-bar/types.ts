/**
 * Import status response from the API
 */
export interface ImportStatus {
	/**
	 * Import mode: 'scheduled' or 'immediate'
	 */
	mode: 'scheduled' | 'immediate';

	/**
	 * Last processed order date (site timezone, 'Y-m-d H:i:s' format)
	 * null if never processed or in immediate mode
	 */
	last_processed_date: string | null;

	/**
	 * Next scheduled import time (site timezone, 'Y-m-d H:i:s' format)
	 * null in immediate mode or if not scheduled
	 */
	next_scheduled: string | null;

	/**
	 * Whether a manual import has been in progress or is due to run soon
	 * null in immediate mode
	 */
	import_in_progress_or_due: boolean | null;
}

/**
 * Return value from useImportStatus hook
 */
export interface UseImportStatusReturn {
	/**
	 * Current import status
	 * null while loading or if fetch failed
	 */
	status: ImportStatus | null;

	/**
	 * Whether the initial status fetch is in progress
	 */
	isLoading: boolean;

	/**
	 * Error message if status fetch failed
	 * null if no error
	 */
	error: string | null;

	/**
	 * Function to trigger a manual import
	 * Calls POST /wc-analytics/imports/trigger
	 */
	triggerImport: () => Promise< void >;

	/**
	 * Whether a manual import trigger is in progress
	 */
	isTriggeringImport: boolean;
}
