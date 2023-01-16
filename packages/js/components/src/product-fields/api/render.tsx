/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { store as productFieldStore } from '../store';
import { ProductFieldDefinition } from '../store/types';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function renderField( name: string, props: Record< string, any > ) {
	const fieldConfig: ProductFieldDefinition =
		select( productFieldStore ).getProductField( name );

	if ( fieldConfig.render ) {
		return <fieldConfig.render { ...props } />;
	}
	if ( fieldConfig.type ) {
		return <InputControl type={ fieldConfig.type } { ...props } />;
	}
	return null;
}
