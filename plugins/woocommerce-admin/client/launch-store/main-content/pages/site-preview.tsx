/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */
import type { MainContentComponentProps } from '../xstate';

export const SitePreviewPage = ( props: MainContentComponentProps ) => {
	return (
		<div
			className={ classnames(
				'launch-store-site-preview-page__container',
				props.className
			) }
		>
			<p>Main Content - Site Preview</p>
		</div>
	);
};
