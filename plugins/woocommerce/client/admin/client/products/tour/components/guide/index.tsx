/**
 * External dependencies
 */
import clsx from 'clsx';
import { useState, useRef, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import PageControl from './page-control';
import type { GuideProps } from './types';

/*
 * This component was copied from @wordpress/components since we needed
 * additional functionality and also found some issues.
 * 1: The Close button was being focused every time the page changed.
 * 2: It was not possible to know if the Guide was closed because the modal was closed or because the Finish button was clicked.
 * 3: It was not possible to know which was the current page when the modal was closed.
 * 4: It was not possible to provide a link to the Finish button.
 *
 * If/when all those are implemented at some point, we can migrate to the original component.
 */
function Guide( {
	className,
	contentLabel,
	finishButtonText = __( 'Finish', 'woocommerce' ),
	finishButtonLink,
	onFinish,
	pages = [],
}: GuideProps ) {
	const guideContainer = useRef< HTMLDivElement >( null );
	const [ currentPage, setCurrentPage ] = useState( 0 );
	const canGoBack = currentPage > 0;
	const canGoForward = currentPage < pages.length - 1;

	const goBack = () => {
		if ( canGoBack ) {
			setCurrentPage( currentPage - 1 );
		}
	};

	const goForward = () => {
		if ( canGoForward ) {
			setCurrentPage( currentPage + 1 );
		}
	};

	if ( pages.length === 0 ) {
		return null;
	}

	return (
		<Modal
			className={ clsx( 'components-guide', className ) }
			title={ contentLabel }
			onRequestClose={ () => {
				onFinish( currentPage, 'close' );
			} }
			onKeyDown={ ( event ) => {
				if ( event.code === 'ArrowLeft' ) {
					goBack();
					// Do not scroll the modal's contents.
					event.preventDefault();
				} else if ( event.code === 'ArrowRight' ) {
					goForward();
					// Do not scroll the modal's contents.
					event.preventDefault();
				}
			} }
			ref={ guideContainer }
		>
			<div className="components-guide__container">
				<div className="components-guide__page">
					{ pages[ currentPage ].image }

					{ pages.length > 1 && (
						<PageControl
							currentPage={ currentPage }
							numberOfPages={ pages.length }
							setCurrentPage={ setCurrentPage }
						/>
					) }

					{ pages[ currentPage ].content }
				</div>

				<div className="components-guide__footer">
					{ canGoBack && (
						<Button
							className="components-guide__back-button"
							variant="tertiary"
							onClick={ goBack }
						>
							{ __( 'Previous', 'woocommerce' ) }
						</Button>
					) }
					{ canGoForward && (
						<Button
							className="components-guide__forward-button"
							variant="primary"
							onClick={ goForward }
						>
							{ __( 'Next', 'woocommerce' ) }
						</Button>
					) }
					{ ! canGoForward && (
						<Button
							className="components-guide__finish-button"
							variant="primary"
							href={ finishButtonLink }
							target={ finishButtonLink ? '_blank' : undefined }
							rel={ finishButtonLink ? 'noopener' : undefined }
							onClick={ () => onFinish( currentPage, 'finish' ) }
						>
							{ finishButtonText }
						</Button>
					) }
				</div>
			</div>
		</Modal>
	);
}

export default Guide;
