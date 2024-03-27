/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import type { MainContentComponentProps } from '../xstate';
import './site-preview.scss';

export const SitePreviewPage = ( props: MainContentComponentProps ) => {
	const siteUrl = getAdminSetting( 'siteUrl' ) + '?site-preview=1';
	const [ isLoading, setIsLoading ] = useState( true );
	const iframeRef = useRef< HTMLIFrameElement >( null );

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
		</div>
	);
};
