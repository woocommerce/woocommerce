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

declare module '@wordpress/block-editor' {
	import type * as blockEditorActions from '@wordpress/block-editor/store/actions';
	import type * as blockEditorSelectors from '@wordpress/block-editor/store/selectors';
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	export * from '@wordpress/block-editor/index';

	type BlockEditorConfig = ReduxStoreConfig<
		unknown,
		{
			[ K in keyof typeof blockEditorActions ]: ( typeof blockEditorActions )[ K ];
		},
		WithState< typeof blockEditorSelectors >
	>;

	type BlockEditorStoreDescriptor = StoreDescriptor< BlockEditorConfig > & {
		name: 'core/block-editor';
	};

	export const store: BlockEditorStoreDescriptor;
}

declare module '@wordpress/data' {
	import type * as blockEditorActions from '@wordpress/block-editor/store/actions';
	import type * as blockEditorSelectors from '@wordpress/block-editor/store/selectors';
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	type BlockEditorConfig = ReduxStoreConfig<
		unknown,
		{
			[ K in keyof typeof blockEditorActions ]: ( typeof blockEditorActions )[ K ];
		},
		WithState< typeof blockEditorSelectors >
	>;

	type BlockEditorStoreDescriptor = StoreDescriptor< BlockEditorConfig > & {
		name: 'core/block-editor';
	};

	interface StoreRegistry {
		'core/block-editor': BlockEditorStoreDescriptor;
	}

	// Override DT's function overloads - these are needed because DT declares its own
	// select/dispatch overloads that would otherwise take precedence
	export function select(
		key: 'core/block-editor'
	): CurriedSelectorsOf< BlockEditorStoreDescriptor >;
	export function dispatch(
		key: 'core/block-editor'
	): ActionCreatorsOf< BlockEditorConfig >;
	export function resolveSelect(
		key: 'core/block-editor'
	): CurriedAndPromisifiedSelectorsOf< BlockEditorStoreDescriptor >;
	export function useDispatch(
		key: 'core/block-editor'
	): ActionCreatorsOf< BlockEditorConfig >;
}
