declare module '@wordpress/data/build-types/redux-store/combine-reducers' {
	export function combineReducers(reducers: any): (state: {} | undefined, action: any) => {};
}
