/**
 * External dependencies
 */
import { createElement, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';
import InserterSidebar from './inserter-sidebar';

export function SecondarySidebar() {
	const { isInserterOpened } = useContext( EditorContext );

	if ( isInserterOpened ) {
		return <InserterSidebar />;
	}

	return null;
}
