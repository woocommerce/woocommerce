/**
 * External dependencies
 */
import classnames from 'classnames';
/**
 * Internal dependencies
 */
import type { MainContentComponentProps } from '../xstate';
export const LoadingPage = ( props: MainContentComponentProps ) => {
	return (
		<div
			className={ classnames(
				'launch-store-loading-page__container',
				props.className
			) }
		>
			<p>Main Content - Loading page</p>
			<button
				onClick={ () => {
					props.sendEventToSidebar( {
						type: 'LAUNCH_STORE_SUCCESS',
					} );
				} }
			>
				Launch Store Success
			</button>
		</div>
	);
};
