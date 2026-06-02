/// <reference path="./base-entity-records.d.ts" />
/// <reference path="./helpers.d.ts" />

declare module '@wordpress/core-data/build-types/entity-types/widget-type' {
	/**
	 * Internal dependencies
	 */
	import type { Context, OmitNevers } from '@wordpress/core-data/build-types/entity-types/helpers';
	import type { BaseEntityRecords as _BaseEntityRecords } from '@wordpress/core-data/build-types/entity-types/base-entity-records';
	declare module '@wordpress/core-data/build-types/entity-types/base-entity-records' {
	    namespace BaseEntityRecords {
	        interface WidgetType<C extends Context> {
	            /**
	             * Unique slug identifying the widget type.
	             */
	            id: string;
	            /**
	             * Human-readable name identifying the widget type.
	             */
	            name: string;
	            /**
	             * Description of the widget.
	             */
	            description: string;
	            /**
	             * Whether the widget supports multiple instances
	             */
	            is_multi: boolean;
	            /**
	             * Class name
	             */
	            classname: string;
	        }
	    }
	}
	export type WidgetType<C extends Context = 'edit'> = OmitNevers<_BaseEntityRecords.WidgetType<C>>;
}
