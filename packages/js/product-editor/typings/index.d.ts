declare module '@woocommerce/settings' {
	export declare function getAdminLink( path: string ): string;
	export declare function getSetting< T >(
		name: string,
		fallback?: unknown,
		filter = ( val: unknown, fb: unknown ) =>
			typeof val !== 'undefined' ? val : fb
	): T;
	export declare function isWpVersion(
		version: string,
		operator: '>' | '>=' | '=' | '<' | '<='
	): boolean;
}

declare module '@wordpress/core-data' {
	function useEntityId( kind: string, name: unknown, id?: string ): any;
	function useEntityProp< T = unknown >(
		kind: string,
		name: string,
		prop: string,
		id?: string
	): [ T, ( value: T ) => void, T ];
	function useEntityRecord< T = unknown >(
		kind: string,
		name: string,
		id: number | string,
		options?: { enabled: boolean }
	): {
		record: T;
		editedRecord: T;
		isResolving: boolean;
		hasResolved: boolean;
	};
	const store: string;
}
declare module '@wordpress/keyboard-shortcuts' {
	function useShortcut(
		name: string,
		callback: ( event: KeyboardEvent ) => void
	): void;
	const store;
}

declare module '@wordpress/router' {
	const privateApis;
}

declare module '@wordpress/edit-site/build-module/components/sync-state-with-url/use-init-edited-entity-from-url' {
	export default function useInitEditedEntityFromURL(): void;
}

declare module '@wordpress/edit-site/build-module/components/sidebar-navigation-screen' {
	const SidebarNavigationScreen: React.FunctionComponent< {
		title: string;
		isRoot: boolean;
		content: JSX.Element;
	} >;
	export default SidebarNavigationScreen;
}

declare module '@wordpress/edit-site/build-module/components/site-hub' {
	const SiteHub: React.FunctionComponent< {
		ref: React.Ref;
		isTransparent: boolean;
	} >;
	export default SiteHub;
}

declare module '@wordpress/interface/build-module/components/pinned-items' {
	const PinnedItems: React.FunctionComponent< {
		scope: string;
		children: React.ReactNode;
	} > & {
		Slot: React.FunctionComponent< {
			children?: React.ReactNode;
			scope: string;
		} >;
	};
	export default PinnedItems;
}

declare module '@wordpress/interface/build-module/components/action-item' {
	const ActionItem: React.FunctionComponent< {
		children: React.ReactNode;
		scope: string;
	} > & {
		Slot: React.FunctionComponent< {
			children?: React.ReactNode;
			name: string;
			label: string;
			as: React.ElementType;
			fillProps: Record< string, unknown >;
		} >;
	};
	export default ActionItem;
}

declare module '@wordpress/interface/build-module/components/interface-skeleton' {
	const InterfaceSkeleton: React.FunctionComponent< {
		children?: React.ReactNode;
		header?: React.ReactNode;
		content?: React.ReactNode;
		actions?: React.ReactNode;
	} >;
	export default InterfaceSkeleton;
}
