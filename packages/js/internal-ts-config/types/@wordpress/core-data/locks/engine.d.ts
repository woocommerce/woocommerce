declare module '@wordpress/core-data/build-types/locks/engine' {
	export default function createLocks(): {
	    acquire: (store: any, path: any, exclusive: any) => Promise<any>;
	    release: (lock: any) => void;
	};
}
