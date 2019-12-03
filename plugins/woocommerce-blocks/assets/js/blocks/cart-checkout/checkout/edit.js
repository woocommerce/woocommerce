/**
 * External dependencies
 */
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block.js';

const Edit = ( { attributes } ) => {
	const { className } = attributes;

	return (
		<div className={ className }>
			<Disabled>
				<Block attributes={ attributes } />
			</Disabled>
		</div>
	);
};

export default Edit;
