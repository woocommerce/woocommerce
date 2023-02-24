/**
 * External dependencies
 */
import { ComponentType } from 'react';

export type ProductFieldDefinition = {
	name: string;
	type?: string;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	render?: ComponentType;
};

export type ProductFieldState = {
	fields: Record< string, ProductFieldDefinition >;
};
