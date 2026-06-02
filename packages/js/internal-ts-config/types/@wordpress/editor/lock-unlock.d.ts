declare module '@wordpress/editor/build-types/lock-unlock' {
	export const lock: (object: unknown, privateData: unknown) => void;
	export const unlock: <T = any>(object: unknown) => T;
}
