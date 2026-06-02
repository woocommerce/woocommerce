/// <reference path="./use-entity-block-editor.d.ts" />
/// <reference path="./use-entity-id.d.ts" />
/// <reference path="./use-entity-prop.d.ts" />
/// <reference path="./use-entity-record.d.ts" />
/// <reference path="./use-entity-records.d.ts" />
/// <reference path="./use-resource-permissions.d.ts" />

declare module '@wordpress/core-data/build-types/hooks' {
	/**
	 * Internal dependencies
	 */
	import type { WithPermissions } from '@wordpress/core-data/build-types/hooks/use-entity-records';
	/**
	 * Utility type that adds permissions to any record type.
	 */
	export type { WithPermissions };
	export { default as useEntityRecord, __experimentalUseEntityRecord, } from '@wordpress/core-data/build-types/hooks/use-entity-record';
	export { default as useEntityRecords, __experimentalUseEntityRecords, } from '@wordpress/core-data/build-types/hooks/use-entity-records';
	export { default as useResourcePermissions, __experimentalUseResourcePermissions, } from '@wordpress/core-data/build-types/hooks/use-resource-permissions';
	export { default as useEntityBlockEditor } from '@wordpress/core-data/build-types/hooks/use-entity-block-editor';
	export { default as useEntityId } from '@wordpress/core-data/build-types/hooks/use-entity-id';
	export { default as useEntityProp } from '@wordpress/core-data/build-types/hooks/use-entity-prop';
}
