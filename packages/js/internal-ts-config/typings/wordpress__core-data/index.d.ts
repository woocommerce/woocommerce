/**
 * Module augmentation for @wordpress/core-data to add PromiseCurriedSignature support.
 */

import type * as ET from '@wordpress/core-data/build-types/entity-types';
import type { GetRecordsHttpQuery } from '@wordpress/core-data/build-types/selectors';

type EntityRecordKey = string | number;

declare module '@wordpress/core-data/build-types/selectors' {
	export interface GetEntityRecord {
		PromiseCurriedSignature: <EntityRecord extends ET.EntityRecord<any> | Partial<ET.EntityRecord<any>>>(
			kind: string,
			name: string,
			key?: EntityRecordKey,
			query?: GetRecordsHttpQuery
		) => Promise<EntityRecord | undefined>;
	}

	export interface GetEntityRecords {
		PromiseCurriedSignature: <EntityRecord extends ET.EntityRecord<any> | Partial<ET.EntityRecord<any>>>(
			kind: string,
			name: string,
			query?: GetRecordsHttpQuery
		) => Promise<EntityRecord[] | null>;
	}
}
