/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { fetchProductPreview } from '../../utils/functions';
import sanitizeHtmlExtended from '~/lib/sanitize-html/sanitize-html-extended.js';
import sanitizeHtmlConfig from './product-preview-sanitize-html-config';
import './product-preview-modal.scss';

interface ProductPreviewModalProps {
	productTitle: string;
	productVendor: JSX.Element | string | null;
	productIcon: string;
	productId: number;
	triggerRef: React.RefObject< HTMLAnchorElement >;
	onOpen?: () => void;
	onClose?: ( closeType?: string ) => void;
}

export default function ProductPreviewModal( {
	productTitle,
	productVendor,
	productIcon,
	productId,
	triggerRef,
	onOpen,
	onClose,
}: ProductPreviewModalProps ) {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ previewContent, setPreviewContent ] = useState< {
		html: string;
		css: string;
	} | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	const closeModal = useCallback(
		( closeType = '' ) => {
			if ( onClose ) {
				onClose( closeType );
			}
			// Return focus to the triggering element after a small delay
			// to ensure layout shifts have completed
			setTimeout( () => {
				if ( triggerRef.current ) {
					triggerRef.current.focus();
				}
			}, 100 );
		},
		[ onClose, triggerRef ]
	);

	// Add event listener for content interactions
	useEffect( () => {
		const handleContentInteraction = ( event: Event ) => {
			const target = event.target as HTMLElement;
			const link = target.closest( 'a' );
			if ( link ) {
				const trackType = link.getAttribute( 'data-iam-tracks' );
				if ( trackType ) {
					closeModal(
						`marketplace_product_preview_modal_${ trackType }_clicked`
					);
				}
			}
		};

		const contentElement = document.querySelector(
			'.woocommerce-marketplace__product-preview-modal__content'
		);
		if ( contentElement ) {
			contentElement.addEventListener(
				'click',
				handleContentInteraction
			);
			return () => {
				contentElement.removeEventListener(
					'click',
					handleContentInteraction
				);
			};
		}
	}, [ closeModal ] );

	// Fetch preview content and record event when the modal mounts
	useEffect( () => {
		const loadPreview = async () => {
			try {
				const response = await fetchProductPreview( productId );
				const previewData = response?.data || response;

				if ( ! previewData?.html || ! previewData?.css ) {
					throw new Error( 'Invalid preview data structure' );
				}

				const sanitizedHtmlObj = sanitizeHtmlExtended(
					previewData.html,
					sanitizeHtmlConfig
				) as { __html?: string };
				const sanitizedHtml = sanitizedHtmlObj?.__html ?? '';

				setPreviewContent( {
					html: sanitizedHtml,
					css: previewData.css,
				} );
				setError( null );
			} catch ( err ) {
				setError(
					__( 'Failed to load product preview.', 'woocommerce' )
				);
			} finally {
				setIsLoading( false );
			}
		};

		loadPreview();
		if ( onOpen ) {
			onOpen();
		}
	}, [ onOpen, productId ] );

	const productHeader = (
		<div className="woocommerce-marketplace__product-preview-modal__header">
			{ productIcon && (
				<img
					className="woocommerce-marketplace__product-preview-modal__icon"
					src={ productIcon }
					alt={ productTitle }
				/>
			) }
			<div className="woocommerce-marketplace__product-preview-modal__header-content">
				<h2>{ productTitle }</h2>
				{ productVendor && (
					<div className="woocommerce-marketplace__product-preview-modal__vendor">
						<span>{ __( 'By', 'woocommerce' ) }</span>{ ' ' }
						{ productVendor }
					</div>
				) }
			</div>
		</div>
	);

	return (
		<Modal
			onRequestClose={ () =>
				closeModal( 'marketplace_product_preview_modal_dismissed' )
			}
			className="woocommerce-marketplace__product-preview-modal"
			closeButtonLabel={ __( 'Close product preview', 'woocommerce' ) }
			size="large"
			focusOnMount="firstElement"
			title={ __( 'Official WooCommerce Marketplace', 'woocommerce' ) }
		>
			{ productHeader }
			<div className="woocommerce-marketplace__product-preview-modal__content">
				{ isLoading && (
					<div className="woocommerce-marketplace__product-preview-modal__loading">
						<Spinner />
					</div>
				) }
				{ error && (
					<div className="woocommerce-marketplace__product-preview-modal__error">
						{ error }
					</div>
				) }
				{ ! isLoading && ! error && previewContent && (
					<>
						<style>{ previewContent.css }</style>
						<div
							dangerouslySetInnerHTML={ {
								__html: previewContent.html,
							} }
						/>
					</>
				) }
			</div>
		</Modal>
	);
}
