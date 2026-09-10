/**
 * External dependencies
 */
import type { Form, FormField } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { SettingsUIViewConfig } from './types';
import type { ViewConfigRuntimeResult } from './view-config-runtime';

export type ViewConfigLayoutDecision =
	| { status: 'loading' }
	| {
			status: 'ready';
			form: Form;
	  };

type ViewConfigResponse = {
	kind?: unknown;
	name?: unknown;
	version?: unknown;
	form?: unknown;
};

const isObject = ( value: unknown ): value is Record< string, unknown > =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

const LAYOUT_TYPES = new Set( [
	'regular',
	'panel',
	'card',
	'row',
	'details',
] );

const hasValidLayout = ( value: Record< string, unknown > ) =>
	typeof value.layout === 'undefined' ||
	( isObject( value.layout ) &&
		typeof value.layout.type === 'string' &&
		LAYOUT_TYPES.has( value.layout.type ) );

const getValidForm = (
	form: unknown,
	knownFieldIds: ReadonlySet< string >
): Form | undefined => {
	if (
		! isObject( form ) ||
		! Array.isArray( form.fields ) ||
		! hasValidLayout( form )
	) {
		return undefined;
	}

	if ( form.fields.length === 0 ) {
		return undefined;
	}

	const seenIds = new Set< string >();
	const normalizedGroups: FormField[] = [];
	for ( const group of form.fields ) {
		if (
			! isObject( group ) ||
			typeof group.id !== 'string' ||
			! group.id ||
			! Array.isArray( group.children ) ||
			group.children.length === 0 ||
			! hasValidLayout( group )
		) {
			return undefined;
		}

		if ( seenIds.has( group.id ) ) {
			return undefined;
		}

		seenIds.add( group.id );
		const normalizedChildren: Array< FormField | string > = [];

		for ( const leaf of group.children ) {
			const id =
				typeof leaf === 'string' ? leaf : isObject( leaf ) && leaf.id;

			if (
				typeof id !== 'string' ||
				! id ||
				( isObject( leaf ) &&
					( 'children' in leaf || ! hasValidLayout( leaf ) ) ) ||
				seenIds.has( id ) ||
				! knownFieldIds.has( id )
			) {
				return undefined;
			}

			seenIds.add( id );
			normalizedChildren.push(
				typeof leaf === 'string'
					? leaf
					: {
							id,
							...( typeof leaf.layout === 'undefined'
								? {}
								: {
										layout: leaf.layout as FormField[ 'layout' ],
								  } ),
					  }
			);
		}

		normalizedGroups.push( {
			id: group.id,
			...( typeof group.label === 'string'
				? { label: group.label }
				: {} ),
			...( typeof group.description === 'string'
				? { description: group.description }
				: {} ),
			...( typeof group.layout === 'undefined'
				? {}
				: { layout: group.layout as FormField[ 'layout' ] } ),
			children: normalizedChildren,
		} );
	}

	return {
		...( typeof form.layout === 'undefined'
			? {}
			: { layout: form.layout as Form[ 'layout' ] } ),
		fields: normalizedGroups,
	};
};

export const selectViewConfigLayout = ( {
	request,
	runtime,
	localForm,
	knownFieldIds,
}: {
	request: SettingsUIViewConfig;
	runtime: ViewConfigRuntimeResult;
	localForm: Form;
	knownFieldIds: ReadonlySet< string >;
} ): ViewConfigLayoutDecision => {
	if ( ! request.supported ) {
		return { status: 'ready', form: localForm };
	}

	if ( runtime.status === 'loading' ) {
		return { status: 'loading' };
	}

	if ( runtime.status === 'failed' ) {
		return { status: 'ready', form: localForm };
	}

	const response = isObject( runtime.config )
		? ( runtime.config as ViewConfigResponse )
		: undefined;
	const form =
		response &&
		response.kind === request.kind &&
		response.name === request.name &&
		( typeof request.version === 'undefined' ||
			response.version === request.version )
			? getValidForm( response.form, knownFieldIds )
			: undefined;

	return form
		? { status: 'ready', form }
		: { status: 'ready', form: localForm };
};
