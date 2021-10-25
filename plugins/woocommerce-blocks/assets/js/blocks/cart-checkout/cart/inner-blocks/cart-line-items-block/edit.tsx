/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';

export const Edit = ( {
	attributes,
}: {
	attributes: { className: string };
} ): JSX.Element => {
	const { className } = attributes;
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Disabled>
				<Block className={ className } />
			</Disabled>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
