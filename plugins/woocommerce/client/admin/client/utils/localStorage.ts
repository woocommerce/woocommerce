type StoredItem< T > = {
	data: T;
	expiry: string;
};

/**
 * This constructor function exists for type safety purposes, so when you create a pair of set/get functions they have the
 *  same type for the value they store/retrieve.
 */
export function createStorageUtils< T >(
	key: string,
	durationInSeconds: number
) {
	return {
		setWithExpiry: ( value: T ): void => {
			const now = new Date();
			const expiry = new Date( now.getTime() + durationInSeconds * 1000 );

			const item: StoredItem< T > = {
				data: value,
				expiry: expiry.toISOString(),
			};

			localStorage.setItem( key, JSON.stringify( item ) );
		},
		getWithExpiry: (): T | null => {
			const itemStr = localStorage.getItem( key );
			if ( ! itemStr ) {
				return null;
			}

			const item: StoredItem< T > = JSON.parse( itemStr );
			const now = new Date();
			if ( now > new Date( item.expiry ) ) {
				localStorage.removeItem( key );
				return null;
			}
			return item.data;
		},
	};
}
