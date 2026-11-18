/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import Noninteractive from '@woocommerce/base-components/noninteractive';

/**
 * Internal dependencies
 */
import Block, { BlockAttributes } from './block';

export const Edit = ( {
	attributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Noninteractive>
				<Block { ...attributes } displayCouponForm />
			</Noninteractive>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
