import type { store } from '@wordpress/core-data/build-types';

declare module '@wordpress/data' {
	interface StoreRegistry {
		core: typeof store;
	}
}

declare module '@wordpress/core-data/build-types/selectors' {
	import type * as ET from '@wordpress/core-data/build-types/entity-types';
	import type { GetRecordsHttpQuery } from '@wordpress/core-data/build-types/selectors';

	type EntityRecordKey = string | number;

	export interface GetEntityRecord {
		PromiseCurriedSignature: <
			EntityRecord extends
				| ET.EntityRecord< any >
				| Partial< ET.EntityRecord< any > >
		>(
			kind: string,
			name: string,
			key?: EntityRecordKey,
			query?: GetRecordsHttpQuery
		) => Promise< EntityRecord | undefined >;
	}

	export interface GetEntityRecords {
		PromiseCurriedSignature: <
			EntityRecord extends
				| ET.EntityRecord< any >
				| Partial< ET.EntityRecord< any > >
		>(
			kind: string,
			name: string,
			query?: GetRecordsHttpQuery
		) => Promise< EntityRecord[] | null >;
	}
}
