/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * This context is a configuration object used for connecting
 * all children blocks in a given tree contained in the context with information
 * about the parent block. Typically this is used for extensibility features.
 *
 * @member {Object} InnerBlockLayoutContext A react context object
 */
const InnerBlockLayoutContext = createContext( {
	parentName: '',
	parentClassName: '',
	isLoading: false,
} );

export const useInnerBlockLayoutContext = () =>
	useContext( InnerBlockLayoutContext );

interface InnerBlockLayoutContextProviderProps {
	parentName?: string;
	parentClassName?: string;
	isLoading?: boolean;
	children: React.ReactNode;
}

export const InnerBlockLayoutContextProvider = ( {
	parentName = '',
	parentClassName = '',
	isLoading = false,
	children,
}: InnerBlockLayoutContextProviderProps ) => {
	const contextValue = {
		parentName,
		parentClassName,
		isLoading,
	};
	return (
		<InnerBlockLayoutContext.Provider value={ contextValue }>
			{ children }
		</InnerBlockLayoutContext.Provider>
	);
};
