/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type { BlockSaveProps } from './types';

const COUPON_CODE_PLACEHOLDER = 'XXXX-XXXXXX-XXXX';

/**
 * Save component for the Coupon Code block.
 *
 * @param {BlockSaveProps} props - Block properties.
 * @return {JSX.Element} The save component.
 */
export function Save( props: BlockSaveProps ): JSX.Element {
	const { attributes } = props;
	const source = attributes.source ?? 'createNew';
	const displayCode =
		source === 'createNew'
			? COUPON_CODE_PLACEHOLDER
			: attributes.couponCode;

	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			{ displayCode && <strong>{ displayCode }</strong> }
		</div>
	);
}
