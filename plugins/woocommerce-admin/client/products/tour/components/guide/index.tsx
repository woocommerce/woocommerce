/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState, useRef, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import PageControl from './page-control';
import type { GuideProps } from './types';

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
			className={ classnames( 'components-guide', className ) }
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
							onClick={ goBack }
						>
							{ __( 'Previous', 'woocommerce' ) }
						</Button>
					) }
					{ canGoForward && (
						<Button
							className="components-guide__forward-button"
							onClick={ goForward }
						>
							{ __( 'Next', 'woocommerce' ) }
						</Button>
					) }
					{ ! canGoForward && (
						<Button
							className="components-guide__finish-button"
							href={ finishButtonLink }
							target={ finishButtonLink ? '_blank' : undefined }
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
