/**
 * DT types are already curried (no state parameter), so we add a fake state param
 * to match the expected ReduxStoreConfig selector shape, which gets curried away.
 */
type WithState< Selectors > = {
	[ K in keyof Selectors ]: Selectors[ K ] extends (
		...args: infer A
	) => infer R
		? ( state: unknown, ...args: A ) => R
		: Selectors[ K ];
};

declare module '@wordpress/editor' {
	import type * as editorActions from '@wordpress/editor/store/actions';
	import type * as editorSelectors from '@wordpress/editor/store/selectors';
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	export * from '@wordpress/editor/index';

	type EditorConfig = ReduxStoreConfig<
		unknown,
		{ [ K in keyof typeof editorActions ]: ( typeof editorActions )[ K ] },
		WithState< typeof editorSelectors >
	>;

	type EditorStoreDescriptor = StoreDescriptor< EditorConfig > & {
		name: 'core/editor';
	};

	export const store: EditorStoreDescriptor;
}

declare module '@wordpress/data' {
	import type * as editorActions from '@wordpress/editor/store/actions';
	import type * as editorSelectors from '@wordpress/editor/store/selectors';
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	type EditorConfig = ReduxStoreConfig<
		unknown,
		{ [ K in keyof typeof editorActions ]: ( typeof editorActions )[ K ] },
		WithState< typeof editorSelectors >
	>;

	type EditorStoreDescriptor = StoreDescriptor< EditorConfig > & {
		name: 'core/editor';
	};

	interface StoreRegistry {
		'core/editor': EditorStoreDescriptor;
	}

	// Override DT's function overloads - these are needed because DT declares its own
	// select/dispatch overloads that would otherwise take precedence
	export function select(
		key: 'core/editor'
	): CurriedSelectorsOf< EditorStoreDescriptor >;
	export function dispatch(
		key: 'core/editor'
	): ActionCreatorsOf< EditorConfig >;
	export function resolveSelect(
		key: 'core/editor'
	): CurriedAndPromisifiedSelectorsOf< EditorStoreDescriptor >;
	export function useDispatch(
		key: 'core/editor'
	): ActionCreatorsOf< EditorConfig >;
}
