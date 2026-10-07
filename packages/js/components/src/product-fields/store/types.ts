/**
 * External dependencies
 */
import { ComponentType, HTMLInputTypeAttribute } from 'react';

export type ProductFieldDefinition = {
	name: string;
	type?: HTMLInputTypeAttribute;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	render?: ComponentType;
	attributes?: Record< string, string >;
};

export type ProductFieldState = {
	fields: Record< string, ProductFieldDefinition >;
};
