declare module '@wordpress/data' {
	import type * as noticesSelectors from '@wordpress/notices/build-types/store/selectors';
	import type * as noticesActions from '@wordpress/notices/build-types/store/actions';
	import type {
		StoreDescriptor,
		ReduxStoreConfig,
	} from '@wordpress/data/build-types/types';

	type NoticesConfig = ReduxStoreConfig<
		unknown,
		{ [ K in keyof typeof noticesActions ]: ( typeof noticesActions )[ K ] },
		{ [ K in keyof typeof noticesSelectors ]: ( typeof noticesSelectors )[ K ] }
	>;

	type NoticesStoreDescriptor = StoreDescriptor< NoticesConfig > & {
		name: 'core/notices';
	};

	interface StoreRegistry {
		'core/notices': NoticesStoreDescriptor;
	}
}
