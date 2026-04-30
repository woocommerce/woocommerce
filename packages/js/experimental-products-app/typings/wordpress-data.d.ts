import type {
	AnyConfig,
	StoreDescriptor,
	UseDispatchReturn,
} from '@wordpress/data';

/**
 * Generic fallback overload for `useDispatch()`.
 *
 * Store-specific overloads (notices, core-data, preferences) are declared in
 * their own type files. This file must be referenced first in `types/index.d.ts`
 * so the fallback has the lowest overload priority — in TypeScript's declaration
 * merging, later `declare module` blocks' overloads are tried first.
 */
declare module '@wordpress/data' {
	export function useDispatch<
		StoreNameOrDescriptor extends
			| undefined
			| string
			| StoreDescriptor< AnyConfig >
	>(
		storeNameOrDescriptor?: StoreNameOrDescriptor
	): UseDispatchReturn< StoreNameOrDescriptor >;
}
