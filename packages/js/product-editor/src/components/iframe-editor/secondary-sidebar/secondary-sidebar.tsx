/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import InserterSidebar from './inserter-sidebar';

type SecondarySidebarProps = {
	isInserterOpened: boolean;
	setIsInserterOpened: ( value: boolean ) => void;
};

export function SecondarySidebar( {
	isInserterOpened,
	setIsInserterOpened,
}: SecondarySidebarProps ) {
	if ( isInserterOpened ) {
		return <InserterSidebar setIsInserterOpened={ setIsInserterOpened } />;
	}

	return null;
}
