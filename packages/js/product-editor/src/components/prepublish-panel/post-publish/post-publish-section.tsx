/**
 * External dependencies
 */
import { createElement, useState, Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, PanelBody, TextControl } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';
import { useCopyToClipboard } from '@wordpress/compose';
import { Ref } from 'react';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useProductScheduled } from '../../../hooks/use-product-scheduled';
import { CopyButtonProps, PostPublishSectionProps } from './types';
import { TRACKS_SOURCE } from '../../../constants';
import { useProductURL } from '../../../hooks/use-product-url';

export function PostPublishSection( { postType }: PostPublishSectionProps ) {
	const { getProductURL } = useProductURL( postType );
	const { isScheduled } = useProductScheduled( postType );

	const [ showCopyConfirmation, setShowCopyConfirmation ] =
		useState< boolean >( false );

	const productURL = getProductURL( isScheduled );

	const CopyButton = ( { text, onCopy, children }: CopyButtonProps ) => {
		const ref = useCopyToClipboard(
			text,
			onCopy
		) as Ref< HTMLButtonElement >;
		return (
			<Button variant="secondary" ref={ ref }>
				{ children }
			</Button>
		);
	};

	const onCopyURL = () => {
		recordEvent( 'product_prepublish_panel', {
			source: TRACKS_SOURCE,
			action: 'copy_product_url',
		} );
		setShowCopyConfirmation( true );
		setTimeout( () => {
			setShowCopyConfirmation( false );
		}, 4000 );
	};

	const onSelectInput = ( event: React.FocusEvent< HTMLInputElement > ) => {
		event.target.select();
	};

	return (
		<PanelBody>
			<p className="post-publish-section__postpublish-subheader">
				<strong>{ __( 'What’s next?', 'woocommerce' ) }</strong>
			</p>
			<div className="post-publish-section__postpublish-post-address-container">
				{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
				{ /* @ts-ignore TextControl requires an 'onChange' but it's not necessary here. */ }
				<TextControl
					className="post-publish-section__postpublish-post-address"
					readOnly
					label={ __( 'product address', 'woocommerce' ) }
					value={ productURL }
					onFocus={ onSelectInput }
				/>

				<div className="post-publish-section__copy-button-wrap">
					<CopyButton text={ productURL } onCopy={ onCopyURL }>
						<>
							{ showCopyConfirmation
								? __( 'Copied!', 'woocommerce' )
								: __( 'Copy', 'woocommerce' ) }
						</>
					</CopyButton>
				</div>
			</div>

			<div className="post-publish-section__postpublish-buttons">
				{ ! isScheduled && (
					<Button variant="primary" href={ productURL }>
						{ __( 'View Product', 'woocommerce' ) }
					</Button>
				) }
				<Button
					variant={ isScheduled ? 'primary' : 'secondary' }
					href={ getNewPath( {}, '/add-product', {} ) }
				>
					{ __( 'Add New Product', 'woocommerce' ) }
				</Button>
			</div>
		</PanelBody>
	);
}
