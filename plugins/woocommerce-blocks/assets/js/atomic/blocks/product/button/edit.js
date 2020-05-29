/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';

export default ( { attributes } ) => {
	return (
		<Disabled>
			<Block { ...attributes } />
		</Disabled>
	);
};
