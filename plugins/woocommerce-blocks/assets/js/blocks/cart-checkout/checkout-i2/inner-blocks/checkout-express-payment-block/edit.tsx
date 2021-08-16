/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import { useBlockPropsWithLocking } from '../../hacks';

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
