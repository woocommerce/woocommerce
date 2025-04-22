/**
 * External dependencies
 */
import {
	createElement,
	createContext,
	useState,
	useContext,
} from '@wordpress/element';

type NewNavigationContextType = {
	showNewNavigation: boolean;
	setShowNewNavigation: ( nav: boolean ) => void;
};
const NewNavigationContext = createContext< NewNavigationContextType | null >(
	null
);

export function NewNavigationProvider( {
	children,
}: {
	children?: React.ReactNode;
} ) {
	const [ showNewNavigation, setShowNewNavigation ] = useState( false );
	return (
		<NewNavigationContext.Provider
			value={ { showNewNavigation, setShowNewNavigation } }
		>
			{ children }
		</NewNavigationContext.Provider>
	);
}
export function useNewNavigation(): [ boolean, ( nav: boolean ) => void ] {
	const context = useContext( NewNavigationContext );
	if ( context ) {
		const { showNewNavigation, setShowNewNavigation } = context;
		return [ showNewNavigation, setShowNewNavigation ];
	}
	return [ false, () => {} ];
}
