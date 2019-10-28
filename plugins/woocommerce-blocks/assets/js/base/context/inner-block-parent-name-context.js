/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

const InnerBlockParentNameContext = createContext( { blockName: null } );

export const useInnerBlockParentNameContext = () =>
	useContext( InnerBlockParentNameContext );
export const InnerBlockParentNameProvider =
	InnerBlockParentNameContext.Provider;
