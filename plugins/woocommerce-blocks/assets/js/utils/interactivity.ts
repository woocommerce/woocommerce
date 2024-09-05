// Util to add the type of another store part.
// eslint-disable-next-line @typescript-eslint/ban-types
export type StorePart< T > = T extends Function
	? T
	: T extends object
	? { [ P in keyof T ]?: StorePart< T[ P ] > }
	: T;
