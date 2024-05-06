/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';
import { useResizeObserver } from '@wordpress/compose';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import ResizableFrame from '~/customize-store/assembler-hub/resizable-frame';
import type { MainContentComponentProps } from '../xstate';
import './site-preview.scss';

export const SitePreviewPage = ( props: MainContentComponentProps ) => {
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?site-preview=1';
	const [ isLoading, setIsLoading ] = useState( true );
	const iframeRef = useRef< HTMLIFrameElement >( null );
	const [ frameResizer, frameSize ] = useResizeObserver();
	const [ isResizableFrameOversized, setIsResizableFrameOversized ] =
		useState( false );

	useEffect( () => {
		const iframeContentWindow = iframeRef.current?.contentWindow;

		const beforeUnloadHandler = () => {
			setIsLoading( true );
		};

		if ( iframeContentWindow ) {
			iframeContentWindow.addEventListener(
				'beforeunload',
				beforeUnloadHandler
			);
		}
		return () => {
			if ( iframeContentWindow ) {
				iframeContentWindow.removeEventListener(
					'beforeunload',
					beforeUnloadHandler
				);
			}
		};
		// IsLoading is a dependency because we want to reset it when the iframe reloads.
	}, [ iframeRef, setIsLoading, isLoading ] );

	return (
		<div
			className={ classnames(
				'launch-store-site-preview-page__container',
				{ 'is-loading': isLoading },
				props.className
			) }
		>
			{ frameResizer }
			{ !! frameSize.width && (
				<motion.div
					initial={ false }
					layout="position"
					className="launch-store-preview-layout__canvas"
				>
					<ResizableFrame
						isReady={ ! isLoading }
						isHandleVisibleByDefault={ false }
						isFullWidth={ false }
						defaultSize={ {
							width: frameSize.width - 24,
							height: frameSize.height,
						} }
						isOversized={ isResizableFrameOversized }
						setIsOversized={ setIsResizableFrameOversized }
						innerContentStyle={ {} }
					>
						{ isLoading && (
							<div className="launch-store-site-preview-site__loading-overlay">
								<Spinner />
							</div>
						) }

						<iframe
							ref={ iframeRef }
							className="launch-store-site__preview-site-iframe"
							src={ siteUrl }
							title="Preview"
							onLoad={ () => setIsLoading( false ) }
						/>
					</ResizableFrame>
				</motion.div>
			) }
		</div>
	);
};
