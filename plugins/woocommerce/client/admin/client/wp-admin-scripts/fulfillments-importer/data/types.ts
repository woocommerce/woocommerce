export interface ImporterRowResult {
	row: number;
	status: 'created' | 'updated' | 'skipped' | 'failed';
	message: string;
	/**
	 * Raw order number value from the CSV, so failed rows can still name the
	 * order the file referred to. Current servers send it for every row;
	 * optional for rows from before the field existed.
	 */
	order_number?: string;
	/** Stable failure code, present on failed rows only. */
	code?: string;
	order_id?: number;
	/** HPOS-aware admin edit URL, present when the order was resolved. */
	order_edit_url?: string;
	fulfillment_id?: number;
	notified?: boolean;
}

export interface ImporterSummary {
	created: number;
	updated: number;
	skipped: number;
	failed: number;
	notified: number;
	rows: ImporterRowResult[];
}

/**
 * Values arrive through wp_localize_script, which casts scalars to strings,
 * so numeric fields must be coerced before use.
 */
export interface ImporterSettings {
	importRoute: string;
	chunkSize: number | string;
	maxRows: number | string;
	providers: Array< { key: string; label: string } >;
}

export type CanonicalColumnKey =
	| 'order_number'
	| 'tracking_number'
	| 'shipment_provider'
	| 'tracking_url'
	| 'items'
	| '';

/**
 * What a CSV column can be mapped to in the UI. The empty string means the
 * column is still unassigned; 'skip' means the merchant chose "Do not import".
 * The distinction lets the mapping screen flag only genuinely unassigned
 * columns when a required field is missing. Neither value is sent on the wire.
 */
export type MappingChoice = CanonicalColumnKey | 'skip';

/**
 * CSV column index => mapping choice.
 */
export type ColumnMapping = Record< number, MappingChoice >;

export interface PrepareResponse {
	token: string;
	headers: string[];
	sample: string[];
	total: number;
	/**
	 * The server returns string-keyed indices ("0", "1", …) per the REST contract.
	 */
	detected_mapping: Record< string, CanonicalColumnKey >;
	delimiter: string;
}

export interface RunChunkError {
	row: number;
	code: string;
	message: string;
}

export interface RunChunkCounts {
	created: number;
	updated: number;
	skipped: number;
	failed: number;
	notified: number;
}

export interface RunChunkResponse {
	processed: number;
	total: number;
	done: boolean;
	counts: RunChunkCounts;
	rows: ImporterRowResult[];
	errors: RunChunkError[];
	summary?: ImporterSummary;
}
