/**
 * Module augmentation for @wordpress/core-data to register in StoreRegistry
 * and add PromiseCurriedSignature support for generic type parameters.
 */

import type { store } from '@wordpress/core-data/build-types';
import type * as ET from '@wordpress/core-data/build-types/entity-types';
import type { GetRecordsHttpQuery } from '@wordpress/core-data/build-types/selectors';

/**
 * Register 'core' store in StoreRegistry for typed string-based access.
 * This enables select('core'), dispatch('core'), and resolveSelect('core')
 * to return properly typed selectors/actions with metadata support.
 */
declare module '@wordpress/data' {
	interface StoreRegistry {
		core: typeof store;
	}
}

/**
 * Add PromiseCurriedSignature to core-data selectors for generic support in resolveSelect.
 */
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
