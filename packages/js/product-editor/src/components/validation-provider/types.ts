/**
 * External dependencies
 */
import { ErrorObject } from 'ajv';

export type EntityConfig = {
	baseURL: string;
};

export type SchemaProperties = {
	type: string;
};

export type Schema = {
	$schema: string;
	properties: SchemaProperties[];
};

export type OptionsResponse = {
	schema: Schema;
};

export type ErrorDictionary = { [ key: string ]: ErrorObject };

export type ValidationProviderProps = {
	schema?: Schema;
};

export type ValidationContextProps = {
	errors: {
		[ key: string ]: ErrorObject;
	};
};
