/// <reference path="./base-entity-records.d.ts" />
/// <reference path="./helpers.d.ts" />

declare module '@wordpress/core-data/build-types/entity-types/menu-location' {
	/**
	 * Internal dependencies
	 */
	import type { Context, OmitNevers } from '@wordpress/core-data/build-types/entity-types/helpers';
	import type { BaseEntityRecords as _BaseEntityRecords } from '@wordpress/core-data/build-types/entity-types/base-entity-records';
	declare module '@wordpress/core-data/build-types/entity-types/base-entity-records' {
	    namespace BaseEntityRecords {
	        interface MenuLocation<C extends Context> {
	            /**
	             * The name of the menu location.
	             */
	            name: string;
	            /**
	             * The description of the menu location.
	             */
	            description: string;
	            /**
	             * The ID of the assigned menu.
	             */
	            menu: number;
	        }
	    }
	}
	export type MenuLocation<C extends Context = 'edit'> = OmitNevers<_BaseEntityRecords.MenuLocation<C>>;
}
