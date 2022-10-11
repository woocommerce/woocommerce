export type ConditionalWrapperProps< T = JSX.Element > = {
	children: T;
	condition: boolean;
	wrapper: ( children: T ) => JSX.Element;
};

export const ConditionalWrapper = < T, >( {
	condition,
	wrapper,
	children,
}: ConditionalWrapperProps< T > ): JSX.Element | T =>
	condition ? wrapper( children ) : children;
