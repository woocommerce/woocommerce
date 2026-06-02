/// <reference path="./constants.d.ts" />

declare module '@wordpress/core-data/build-types/hooks/use-resource-permissions' {
	import { Status } from '@wordpress/core-data/build-types/hooks/constants';
	interface GlobalResourcePermissionsResolution {
	    /** Can the current user create new resources of this type? */
	    canCreate: boolean;
	}
	interface SpecificResourcePermissionsResolution {
	    /** Can the current user update resources of this type? */
	    canUpdate: boolean;
	    /** Can the current user delete resources of this type? */
	    canDelete: boolean;
	}
	interface ResolutionDetails {
	    /** Resolution status */
	    status: Status;
	    /**
	     * Is the data still being resolved?
	     */
	    isResolving: boolean;
	}
	/**
	 * Is the data resolved by now?
	 */
	type HasResolved = boolean;
	type ResourcePermissionsResolution<IdType> = [
	    HasResolved,
	    ResolutionDetails & GlobalResourcePermissionsResolution & (IdType extends void ? SpecificResourcePermissionsResolution : {})
	];
	type EntityResource = {
	    kind: string;
	    name: string;
	    id?: string | number;
	};
	declare function useResourcePermissions<IdType = void>(resource: string, id?: IdType): ResourcePermissionsResolution<IdType>;
	declare function useResourcePermissions<IdType = void>(resource: EntityResource, id?: never): ResourcePermissionsResolution<IdType>;
	export default useResourcePermissions;
	export declare function __experimentalUseResourcePermissions(resource: string, id?: unknown): ResourcePermissionsResolution<unknown>;
}
