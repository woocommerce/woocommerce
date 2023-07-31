/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Template } from './types';

const DEFAULT_BLOCK_TEMPLATE_CONTEXT = {
	hideBlock: () => null,
	hiddenBlocks: [],
	templates: [],
	selectedTemplate: null,
	selectTemplate: () => null,
	unhideBlock: () => null,
};

type ContextType = {
	hideBlock: ( blockId: string ) => void;
	hiddenBlocks: string[];
	templates: Template[];
	selectedTemplate: string | null;
	selectTemplate: ( templateId: string ) => void;
	unhideBlock: ( blockId: string ) => void;
};

const Context = createContext< ContextType >( DEFAULT_BLOCK_TEMPLATE_CONTEXT );
export const { Provider } = Context;

/**
 * A hook that returns the block edit context.
 *
 * @return {Object} Block edit context
 */
export function useBlockTemplate() {
	return useContext( Context );
}
