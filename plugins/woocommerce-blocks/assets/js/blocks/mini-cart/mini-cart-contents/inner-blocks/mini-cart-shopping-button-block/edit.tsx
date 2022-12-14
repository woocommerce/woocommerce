/**
 * External dependencies
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';
import Button from '@woocommerce/base-components/button';

/**
 * Internal dependencies
 */
import { defaultStartShoppingButtonLabel } from './constants';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		startShoppingButtonLabel: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const { startShoppingButtonLabel } = attributes;

	return (
		<div { ...blockProps }>
			<div className="wp-block-button aligncenter">
				<Button className="wc-block-mini-cart__shopping-button">
					<RichText
						multiline={ false }
						allowedFormats={ [] }
						value={ startShoppingButtonLabel }
						placeholder={ defaultStartShoppingButtonLabel }
						onChange={ ( content ) => {
							setAttributes( {
								startShoppingButtonLabel: content,
							} );
						} }
					/>
				</Button>
			</div>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() }></div>;
};
