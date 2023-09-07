/**
 * External dependencies
 */
import { createElement, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EditorContext } from '../context';
import InserterSidebar from './inserter-sidebar';
import { DocumentOverviewSidebar } from './document-overview-sidebar';

export function SecondarySidebar() {
	const { isInserterOpened, isDocumentOverviewOpened: isListViewOpened } =
		useContext( EditorContext );

	if ( isInserterOpened ) {
		return <InserterSidebar />;
	}

	if ( isListViewOpened ) {
		return <DocumentOverviewSidebar />;
	}

	return null;
}
