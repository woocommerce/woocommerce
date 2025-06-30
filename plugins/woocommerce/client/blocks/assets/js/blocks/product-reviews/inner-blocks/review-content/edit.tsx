/**
 * External dependencies
 */
import clsx from 'clsx';
import { _x } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';
import { Disabled } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import type { BlockEditProps } from '@wordpress/blocks';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';

export default function Edit( {
	setAttributes,
	attributes: { textAlign },
	context: { commentId },
}: BlockEditProps< {
	textAlign: string;
} > & {
	context: { commentId: number };
} ) {
	const blockProps = useBlockProps( {
		className: clsx( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );

	const [ content ] = useEntityProp(
		'root',
		'comment',
		'content',
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-expect-error - the type of useEntityProp is not correct
		commentId
	);

	const blockControls = (
		<BlockControls>
			<AlignmentControl
				value={ textAlign }
				onChange={ ( newAlign: string | undefined ) => {
					if ( newAlign !== undefined ) {
						setAttributes( { textAlign: newAlign } );
					}
				} }
			/>
		</BlockControls>
	);

	if ( ! commentId || ! content ) {
		return (
			<>
				{ blockControls }
				<div { ...blockProps }>
					<p>
						{ _x( 'Review Content', 'block title', 'woocommerce' ) }
					</p>
				</div>
			</>
		);
	}

	return (
		<>
			{ blockControls }
			<div { ...blockProps }>
				<Disabled>
					<RawHTML key="html">{ content.rendered }</RawHTML>
				</Disabled>
			</div>
		</>
	);
}
