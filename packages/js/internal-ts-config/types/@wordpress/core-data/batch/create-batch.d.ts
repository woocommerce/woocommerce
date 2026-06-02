declare module '@wordpress/core-data/build-types/batch/create-batch' {
	/**
	 * Creates a batch, which can be used to combine multiple API requests into one
	 * API request using the WordPress batch processing API (/v1/batch).
	 *
	 * ```
	 * const batch = createBatch();
	 * const dunePromise = batch.add( {
	 *   path: '/v1/books',
	 *   method: 'POST',
	 *   data: { title: 'Dune' }
	 * } );
	 * const lotrPromise = batch.add( {
	 *   path: '/v1/books',
	 *   method: 'POST',
	 *   data: { title: 'Lord of the Rings' }
	 * } );
	 * const isSuccess = await batch.run(); // Sends one POST to /v1/batch.
	 * if ( isSuccess ) {
	 *   console.log(
	 *     'Saved two books:',
	 *     await dunePromise,
	 *     await lotrPromise
	 *   );
	 * }
	 * ```
	 *
	 * @param {Function} [processor] Processor function. Can be used to replace the
	 *                               default functionality which is to send an API
	 *                               request to /v1/batch. Is given an array of
	 *                               inputs and must return a promise that
	 *                               resolves to an array of objects containing
	 *                               either `output` or `error`.
	 */
	export default function createBatch(processor?: Function): {
	    /**
	     * Adds an input to the batch and returns a promise that is resolved or
	     * rejected when the input is processed by `batch.run()`.
	     *
	     * You may also pass a thunk which allows inputs to be added
	     * asynchronously.
	     *
	     * ```
	     * // Both are allowed:
	     * batch.add( { path: '/v1/books', ... } );
	     * batch.add( ( add ) => add( { path: '/v1/books', ... } ) );
	     * ```
	     *
	     * If a thunk is passed, `batch.run()` will pause until either:
	     *
	     * - The thunk calls its `add` argument, or;
	     * - The thunk returns a promise and that promise resolves, or;
	     * - The thunk returns a non-promise.
	     *
	     * @param {any|Function} inputOrThunk Input to add or thunk to execute.
	     *
	     * @return {Promise|any} If given an input, returns a promise that
	     *                       is resolved or rejected when the batch is
	     *                       processed. If given a thunk, returns the return
	     *                       value of that thunk.
	     */
	    add(inputOrThunk: any | Function): Promise<any> | any;
	    /**
	     * Runs the batch. This calls `batchProcessor` and resolves or rejects
	     * all promises returned by `add()`.
	     *
	     * @return {Promise<boolean>} A promise that resolves to a boolean that is true
	     *                   if the processor returned no errors.
	     */
	    run(): Promise<boolean>;
	};
}
