/**
 * External dependencies
 */
import {
	useBlockProps,
	RichText,
	__experimentalUseColorProps as useColorProps,
} from '@wordpress/block-editor';

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
	const blockProps = useBlockProps( {
		className: 'wp-block-button aligncenter',
	} );
	const colorProps = useColorProps( attributes );
	const { startShoppingButtonLabel } = attributes;

	// Same markup and classes as a core Button block, so the theme's button styles apply in the editor too.
	const linkClassName = [
		'wp-block-button__link',
		'wp-element-button',
		'wc-block-mini-cart__shopping-button',
		colorProps.className,
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div { ...blockProps }>
			<RichText
				tagName="a"
				className={ linkClassName }
				style={ colorProps.style }
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
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() }></div>;
};
