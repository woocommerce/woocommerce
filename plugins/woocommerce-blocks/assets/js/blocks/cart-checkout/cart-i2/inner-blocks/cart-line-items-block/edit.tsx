/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useBlockPropsWithLocking } from '../../hacks';

import Block from './block';

export const Edit = ( {
	attributes,
}: {
	attributes: {
		lock: {
			move: boolean;
			remove: boolean;
		};
	};
} ): JSX.Element => {
	const blockProps = useBlockPropsWithLocking( { attributes } );

	return (
		<div { ...blockProps }>
			<Block />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
