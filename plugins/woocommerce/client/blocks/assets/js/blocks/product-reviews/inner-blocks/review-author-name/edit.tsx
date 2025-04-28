/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, _x } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import type { BlockEditProps } from '@wordpress/blocks';
import {
	// @ts-expect-error AlignmentControl is not exported from @wordpress/block-editor
	AlignmentControl,
	BlockControls,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
// Fix dependency group error.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreStore } from '@wordpress/core-data';
// eslint-disable-next-line @woocommerce/dependency-group
import { PanelBody, ToggleControl } from '@wordpress/components';

type WordPressComment = {
	author_name?: string;
	author: number;
};

type User = {
	name?: string;
};

export default function Edit( {
	attributes: { isLink, linkTarget, textAlign },
	context: { commentId },
	setAttributes,
}: BlockEditProps< {
	isLink: boolean;
	linkTarget: string;
	textAlign: string;
} > & {
	context: { commentId: string };
} ) {
	const blockProps = useBlockProps( {
		className: clsx( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );
	let displayName = useSelect(
		( select ) => {
			const { getEntityRecord } = select( coreStore );

			const comment = getEntityRecord(
				'root',
				'comment',
				commentId
			) as Comment | null;
			const authorName = comment?.author_name;

			if ( comment && ! authorName ) {
				const user = getEntityRecord(
					'root',
					'user',
					comment.author
				) as User | null;
				return user?.name ?? __( 'Anonymous', 'woocommerce' );
			}
			return authorName ?? '';
		},
		[ commentId ]
	);

	const blockControls = (
		<BlockControls>
			<AlignmentControl
				value={ textAlign }
				onChange={ ( newAlign: string | undefined ) => {
					if ( typeof newAlign === 'string' ) {
						setAttributes( { textAlign: newAlign } );
					}
				} }
			/>
		</BlockControls>
	);

	const inspectorControls = (
		<InspectorControls>
			<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Link to authors URL', 'woocommerce' ) }
					onChange={ () => setAttributes( { isLink: ! isLink } ) }
					checked={ isLink }
				/>
				{ isLink && (
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open in new tab', 'woocommerce' ) }
						onChange={ ( value ) =>
							setAttributes( {
								linkTarget: value ? '_blank' : '_self',
							} )
						}
						checked={ linkTarget === '_blank' }
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);

	if ( ! commentId || ! displayName ) {
		displayName = _x( 'Review Author', 'block title', 'woocommerce' );
	}

	const displayAuthor = isLink ? (
		<a
			href="#review-author-pseudo-link"
			onClick={ ( event ) => event.preventDefault() }
		>
			{ displayName }
		</a>
	) : (
		displayName
	);
	return (
		<>
			{ inspectorControls }
			{ blockControls }
			<div { ...blockProps }>{ displayAuthor }</div>
		</>
	);
}
