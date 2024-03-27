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

	return (
		<div
			className={ classnames(
				'launch-store-site-preview-page__container',
				{ 'is-loading': isLoading },
				props.className
			) }
		>
			{ isLoading && (
				<div className="launch-store-site-preview-site_spinner">
					<Spinner />
				</div>
			) }
			<iframe
				className="launch-store-site__preview-site-iframe"
				src={ siteUrl }
				title="Preview"
				onLoad={ () => setIsLoading( false ) }
			/>
		</div>
	);
};
