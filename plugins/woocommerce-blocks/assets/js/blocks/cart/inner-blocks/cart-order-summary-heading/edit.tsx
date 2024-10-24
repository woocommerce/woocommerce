/**
 * External dependencies
 */
import { PlainText, useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './editor.scss';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		content: string;
		className: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const { content = '', className = '' } = attributes;
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<h2 className={ clsx( className, 'wc-block-cart__totals-title' ) }>
				<PlainText
					className={ '' }
					value={ content }
					onChange={ ( value ) =>
						setAttributes( { content: value } )
					}
					style={ { backgroundColor: 'transparent' } }
				/>
			</h2>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
