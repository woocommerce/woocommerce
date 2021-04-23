export const isNull = < T >( term: T | null ): term is null => {
	return term === null;
};

export const isNumber = < U >( term: number | U ): term is number => {
	return typeof term === 'number';
};

export const isString = < U >( term: string | U ): term is string => {
	return typeof term === 'string';
};

export const isObject = < T extends Record< string, unknown >, U >(
	term: T | U
): term is NonNullable< T > => {
	return ! isNull( term ) && typeof term === 'object';
};

export function objectHasProp< P extends PropertyKey >(
	target: Record< string, unknown >,
	property: P
): target is { [ K in P ]: unknown } {
	// The `in` operator throws a `TypeError` for non-object values.
	return property in target;
}

// eslint-disable-next-line @typescript-eslint/ban-types
export const isFunction = < T extends Function, U >(
	term: T | U
): term is T => {
	return typeof term === 'function';
};
