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
	restNamespace: string;
	importRoute: string;
	providers: Array< { key: string; label: string } >;
}
