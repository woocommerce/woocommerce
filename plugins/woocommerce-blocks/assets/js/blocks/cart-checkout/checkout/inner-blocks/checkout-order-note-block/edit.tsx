/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Disabled>
				<Block />
			</Disabled>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
