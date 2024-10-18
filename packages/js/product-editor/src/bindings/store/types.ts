/**
 * Internal dependencies
 */
import { BindingSourceHandlerProps } from '../types';
import { ACTION_REGISTER_BINDINGS_SOURCE } from './constants';

/*
 * Actions types
 */
export type RegisterSourceAction = {
	type: typeof ACTION_REGISTER_BINDINGS_SOURCE;
} & BindingSourceHandlerProps;

export type BindingsAction = RegisterSourceAction | { type: 'UNKNOWN' };

export type BindingsSourcesProps = Record<
	string,
	Omit< BindingSourceHandlerProps, 'name' >
>;

export type BindingsStateProps = {
	sources: BindingsSourcesProps;
};
