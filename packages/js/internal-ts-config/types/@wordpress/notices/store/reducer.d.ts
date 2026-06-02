declare module '@wordpress/notices/build-types/store/reducer' {
	export default notices;
	/**
	 * Reducer returning the next notices state. The notices state is an object
	 * where each key is a context, its value an array of notice objects.
	 *
	 * @param {Object} state  Current state.
	 * @param {Object} action Dispatched action.
	 *
	 * @return {Object} Updated state.
	 */
	declare const notices: any;
}
