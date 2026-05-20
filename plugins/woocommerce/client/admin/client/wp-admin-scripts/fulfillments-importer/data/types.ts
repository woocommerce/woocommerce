export interface ImporterRowResult {
	row: number;
	status: 'created' | 'updated' | 'skipped' | 'failed';
	message: string;
	order_id?: number;
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

export interface ImporterSettings {
	importRoute: string;
	chunkSize: number;
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
 * Canonical column index → canonical column key. Empty string means "do not import this column".
 */
export type ColumnMapping = Record< number, CanonicalColumnKey >;

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
	errors: RunChunkError[];
	summary?: ImporterSummary;
}
