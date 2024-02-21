/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import type { CartItem } from '@woocommerce/type-defs/cart';

/**
 * Internal dependencies
 */
import Block from './block';

export const Edit = ( {
	attributes,
	context,
}: {
	attributes: { className: string };
	context: {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		'woocommerce/single-cart-line-item': CartItem | Record< string, never >;
	};
} ): JSX.Element => {
	const { className } = attributes;

	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<Noninteractive>
				<Block
					className={ className }
					lineItem={ context[ 'woocommerce/single-cart-line-item' ] }
				/>
			</Noninteractive>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
