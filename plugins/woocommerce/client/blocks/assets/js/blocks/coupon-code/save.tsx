/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type { BlockSaveProps } from './types';

/**
 * Save component for the Coupon Code block.
 *
 * @param {BlockSaveProps} props - Block properties.
 * @return {JSX.Element} The save component.
 */
export function Save( props: BlockSaveProps ): JSX.Element {
	const { attributes } = props;
	const couponCode = attributes.couponCode as string;

	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			{ couponCode && <strong>{ couponCode }</strong> }
		</div>
	);
}
