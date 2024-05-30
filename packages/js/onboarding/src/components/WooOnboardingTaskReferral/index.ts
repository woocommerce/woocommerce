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
	durationInSeconds: number,
	type: 'session' | 'local' = 'local'
) {
	const storage = type === 'session' ? sessionStorage : localStorage;
	return {
		setWithExpiry: ( value: T ): void => {
			const now = new Date();
			const expiry = new Date( now.getTime() + durationInSeconds * 1000 );

			const item: StoredItem< T > = {
				data: value,
				expiry: expiry.toISOString(),
			};

			storage.setItem( key, JSON.stringify( item ) );
		},
		getWithExpiry: (): T | null => {
			const itemStr = storage.getItem( key );
			if ( ! itemStr ) {
				return null;
			}

			const item: StoredItem< T > = JSON.parse( itemStr );
			const now = new Date();
			if ( now > new Date( item.expiry ) ) {
				storage.removeItem( key );
				return null;
			}
			return item.data;
		},
		remove: (): void => {
			storage.removeItem( key );
		},
	};
}

export type TaskReferralRecord = {
	referrer: string;
	returnUrl: string;
};

/**
 * Returns a pair of getter and setter for the task referral storage.
 *
 * @param {Object} options                  - The options object.
 * @param {string} options.taskId           - Name of the task.
 * @param {number} options.referralLifetime - Lifetime of the referral. Default of 60 seconds is a reasonable amount of time for referral handoff lifetime in most cases, since it really only needs to account for the page load duration
 */
export const accessTaskReferralStorage = ( {
	taskId,
	referralLifetime = 60,
}: {
	taskId: string;
	referralLifetime?: number;
} ) =>
	createStorageUtils< TaskReferralRecord >(
		`wc_admin_task_referral_${ taskId }`,
		referralLifetime,
		'session'
	);
