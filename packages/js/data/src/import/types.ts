type SchedulerName = 'orders' | 'customers';

type isImporting = {
	is_importing: boolean;
};

type SchedulerImportStatus = {
	[ schedulerName in SchedulerName ]: {
		imported: number;
		totals: number;
	};
} & {
	imported_from?: string | number;
};

export type ImportStatus =
	| isImporting
	| ( isImporting & SchedulerImportStatus );

export type ImportStatusQuery = number;

export type ImportTotals = {
	[ schedulerName in SchedulerName ]: number;
};

export type ImportTotalsQuery = {
	skip_existing: boolean;
	days: number;
};

export type ImportState = {
	activeImport: boolean;
	importStatus:
		| Record< string, never >
		| {
				[ queryString: string ]: ImportStatus;
		  };
	importTotals:
		| Record< string, never >
		| {
				[ queryString: string ]: ImportTotals;
		  };
	errors: {
		[ queryString: string ]: unknown;
	};
	lastImportStartTimestamp: number;
	period: {
		date: string;
		label: string;
	};
	skipPrevious: boolean;
};
