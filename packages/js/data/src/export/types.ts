export type ExportArgs = {
	[ key: string ]: unknown;
};

export type SelectorArgs = {
	type: string;
	args: ExportArgs;
};

export type ExportState = {
	errors: {
		[ selector: string ]: {
			[ hashExportArgs: string ]: unknown;
		};
	};
	requesting: {
		[ selector: string ]: {
			[ hashExportArgs: string ]: boolean;
		};
	};
	exportMeta: {
		[ exportId: string ]: {
			exportType: string;
			exportArgs: ExportArgs;
		};
	};
	exportIds: {
		[ exportType: string ]: {
			[ hashExportArgs: string ]: string;
		};
	};
};
