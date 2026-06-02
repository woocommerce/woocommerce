declare module '@wordpress/core-data/build-types/locks/actions' {
	export default function createLocksActions(): {
	    __unstableAcquireStoreLock: (store: any, path: any, { exclusive }: {
	        exclusive: any;
	    }) => () => Promise<any>;
	    __unstableReleaseStoreLock: (lock: any) => () => void;
	};
}
