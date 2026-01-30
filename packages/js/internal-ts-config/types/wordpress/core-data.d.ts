declare module '@wordpress/data' {
	import type * as coreDataSelectors from '@wordpress/core-data/build-types/selectors';
	import type * as coreDataActions from '@wordpress/core-data/build-types/actions';
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	type CoreDataConfig = ReduxStoreConfig<
		unknown,
		{ [ K in keyof typeof coreDataActions ]: ( typeof coreDataActions )[ K ] },
		{ [ K in keyof typeof coreDataSelectors ]: ( typeof coreDataSelectors )[ K ] }
	>;

	type CoreDataStoreDescriptor = StoreDescriptor< CoreDataConfig > & {
		name: 'core';
	};

	interface StoreRegistry {
		core: CoreDataStoreDescriptor;
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
