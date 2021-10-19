/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

export const Columns = ( {
	children,
	...props
}: {
	children?: React.ReactNode;
} ): JSX.Element => {
	const blockProps = useBlockProps( props );

	return <div { ...blockProps }>{ children }</div>;
};
