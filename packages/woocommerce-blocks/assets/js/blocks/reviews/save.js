/**
 * Internal dependencies
 */
import './editor.scss';
import { getBlockClassName, getDataAttrs } from './utils.js';

export default ( { attributes } ) => {
	return (
		<div
			className={ getBlockClassName( attributes ) }
			{ ...getDataAttrs( attributes ) }
		/>
	);
};
