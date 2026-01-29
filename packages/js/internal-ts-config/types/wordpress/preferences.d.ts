declare module '@wordpress/preferences' {
	import type { StoreDescriptor, ReduxStoreConfig } from '@wordpress/data/build-types/types';

	type PreferencesConfig = ReduxStoreConfig<
		unknown,
		{
			set: ( scope: string, name: string, value: unknown ) => object;
			setDefaults: ( scope: string, defaults: Record< string, unknown > ) => object;
			toggle: ( scope: string, name: string ) => object;
		},
		{
			get: {
				( state: unknown, scope: string, name: string ): unknown;
				CurriedSignature: < T = unknown >( scope: string, name: string ) => T;
			};
		}
	>;

	type PreferencesStoreDescriptor = StoreDescriptor< PreferencesConfig > & {
		name: 'core/preferences';
	};

	export const store: PreferencesStoreDescriptor;
	export const PreferenceToggleMenuItem: any;
}

declare module '@wordpress/data' {
	import type { StoreDescriptor, ReduxStoreConfig } from '@wordpress/data/build-types/types';

	type PreferencesConfig = ReduxStoreConfig<
		unknown,
		{
			set: ( scope: string, name: string, value: unknown ) => object;
			setDefaults: ( scope: string, defaults: Record< string, unknown > ) => object;
			toggle: ( scope: string, name: string ) => object;
		},
		{
			get: {
				( state: unknown, scope: string, name: string ): unknown;
				CurriedSignature: < T = unknown >( scope: string, name: string ) => T;
			};
		}
	>;

	type PreferencesStoreDescriptor = StoreDescriptor< PreferencesConfig > & {
		name: 'core/preferences';
	};

	interface StoreRegistry {
		'core/preferences': PreferencesStoreDescriptor;
	}
}
