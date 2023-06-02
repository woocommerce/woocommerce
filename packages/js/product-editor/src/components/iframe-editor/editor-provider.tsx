/**
 * External dependencies
 */
import {
	createRegistry,
	DataRegistry,
	RegistryProvider,
	useRegistry,
} from '@wordpress/data';
import { createElement, useEffect, useState } from '@wordpress/element';
import { storeConfig as editorStoreConfig } from '@wordpress/editor';

type EditorProviderProps = {
	children: JSX.Element | JSX.Element[];
};

export function EditorProvider( { children }: EditorProviderProps ) {
	const [ subRegistry, setSubRegistry ] = useState< DataRegistry | null >(
		null
	);
	const registry = useRegistry();

	console.log( registry );

	useEffect( () => {
		const newRegistry = createRegistry( {}, registry );
		newRegistry.registerStore( 'core/editor', editorStoreConfig );
		setSubRegistry( newRegistry );
	}, [ registry ] );

	if ( ! subRegistry ) {
		return null;
	}

	console.log( subRegistry );

	return (
		<RegistryProvider value={ subRegistry }>{ children }</RegistryProvider>
	);
}
