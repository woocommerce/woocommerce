/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import type { FunctionComponent, JSX } from 'react';

export function Edit< T >( Block: FunctionComponent< T > ) {
	return function WithBlock( props: T ): JSX.Element {
		const blockProps = useBlockProps();

		return <Block { ...props } blockProps={ blockProps } />;
	};
}
