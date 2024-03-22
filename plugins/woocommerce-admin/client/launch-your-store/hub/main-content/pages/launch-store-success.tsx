/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import type { MainContentComponentProps } from '../xstate';
export const LaunchYourStoreSuccess = ( props: MainContentComponentProps ) => {
	return (
		<div
			className={ classnames(
				'launch-store-success-page__container',
				props.className
			) }
		>
			<p>Main Content - Site Launch Store Success Page</p>
		</div>
	);
};
