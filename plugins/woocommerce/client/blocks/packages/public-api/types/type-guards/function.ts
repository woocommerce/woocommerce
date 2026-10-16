// eslint-disable-next-line @typescript-eslint/no-unsafe-function-type
export const isFunction = < T extends Function, U >(
	term: T | U
): term is T => {
	return typeof term === 'function';
};
