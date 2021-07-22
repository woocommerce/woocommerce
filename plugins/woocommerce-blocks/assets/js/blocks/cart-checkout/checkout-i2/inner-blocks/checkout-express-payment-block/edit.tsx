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

export const Edit = (): JSX.Element => {
	const blockProps = useBlockPropsWithLocking();

	return (
		<div { ...blockProps }>
			<Block />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
