/**
 * External dependencies
 */
import {
	Block,
	BlockConfiguration,
	registerBlockType,
} from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { ComponentType } from 'react';

import type { BlockEditProps } from '@wordpress/blocks';

interface BlockRepresentation< T extends Record< string, object > > {
	name?: string;
	metadata: BlockConfiguration< T >;
	settings: Omit< Partial< BlockConfiguration< T > >, 'edit'> & {
		edit?: EditContexts<T> | EditComponent<T>
	};
}

type EditComponent<T extends Record< string, any > = Record< string, any >> = ComponentType<BlockEditProps<T>>;

interface EditContexts<T extends Record< string, any > = Record< string, any >> {
    [name: string]: EditComponent<T>;
}

function getEditComponent< T extends Record< string, any > = Record< string, any >>( edit?: EditContexts<T> | EditComponent<T> ): EditComponent<T> {
	if ( typeof edit === 'function' ) {
		return edit;
	}

	return ( props: BlockEditProps<T> ) => {
		if ( ! edit ) {
			return null;
		}

		// @ts-ignore Context should exist.
        const { uiContext } = props.context;
	
		if ( edit.hasOwnProperty( uiContext as string ) ) {
			const Component = edit[ uiContext as string ];
			return <Component { ...props } />;
		}

		const DefaultComponent = edit.default;
		return <DefaultComponent { ...props } />;
	}
}

/**
 * Function to register an individual block.
 *
 * @param block The block to be registered.
 * @return The block, if it has been successfully registered; otherwise `undefined`.
 */
export function registerWooBlock<
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any > = Record< string, any >
>( block: BlockRepresentation< T > ): Block< T > | undefined {
	if ( ! block ) {
		return;
	}
	const { metadata, settings, name } = block;
	const { edit } = settings;
	const editComponent = getEditComponent< T >( edit );
	return registerBlockType< T >( { name, ...metadata }, { ...settings, edit: editComponent } );
}
