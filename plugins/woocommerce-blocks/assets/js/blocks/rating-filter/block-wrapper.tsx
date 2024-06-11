/**
 * External dependencies
 */
import clsx from 'clsx';
import { useStyleProps } from '@woocommerce/base-hooks';
import { isString } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import Block from './block';
import { parseAttributes } from './utils';

const BlockWrapper = ( props: Record< string, unknown > ) => {
	const styleProps = useStyleProps( props );
	const parsedBlockAttributes = parseAttributes( props );

	return (
		<div
			className={ clsx(
				isString( props.className ) ? props.className : '',
				styleProps.className
			) }
			style={ styleProps.style }
		>
			<Block isEditor={ false } attributes={ parsedBlockAttributes } />
		</div>
	);
};

export default BlockWrapper;
