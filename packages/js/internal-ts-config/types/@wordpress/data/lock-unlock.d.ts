declare module '@wordpress/data/build-types/lock-unlock' {
	export declare const lock: (object: unknown, privateData: unknown) => void, unlock: <T = any>(object: unknown) => T;
}
