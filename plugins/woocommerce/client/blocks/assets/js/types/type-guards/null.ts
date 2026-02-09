export const isNull = < T >( term: T | null ): term is null => {
	return term === null;
};

export function nonNullable< T >( value: T ): value is NonNullable< T > {
	return value !== null && value !== undefined;
}
