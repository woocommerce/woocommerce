/**
 * External dependencies
 */
import { Suspense } from '@wordpress/element';
import classnames from 'classnames';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useFocusOnMount from '../../hooks/useFocusOnMount';
import useFocusOutside from '../../hooks/useFocusOutside';

export const Panel = ( {
	content,
	isPanelOpen,
	currentTab,
	isPanelSwitching,
	tab,
	closePanel,
	clearPanel,
} ) => {
	const handleFocusOutside = ( event ) => {
		const isClickOnModalOrSnackbar =
			event.target.closest(
				'.woocommerce-inbox-dismiss-confirmation_modal'
			) || event.target.closest( '.components-snackbar__action' );

		if ( isPanelOpen && ! isClickOnModalOrSnackbar ) {
			closePanel();
		}
	};

	const ref = useFocusOnMount();
	const useFocusOutsideProps = useFocusOutside( handleFocusOutside );

	if ( ! tab ) {
		return <div className="woocommerce-layout__activity-panel-wrapper" />;
	}

	if ( ! content ) {
		return null;
	}

	const classNames = classnames(
		'woocommerce-layout__activity-panel-wrapper',
		{
			'is-open': isPanelOpen,
			'is-switching': isPanelSwitching,
		}
	);

	return (
		<div
			className={ classNames }
			tabIndex={ 0 }
			role="tabpanel"
			aria-label={ tab.title }
			onTransitionEnd={ clearPanel }
			onAnimationEnd={ clearPanel }
			{ ...useFocusOutsideProps }
			ref={ ref }
		>
			<div
				className="woocommerce-layout__activity-panel-content"
				key={ 'activity-panel-' + currentTab }
				id={ 'activity-panel-' + currentTab }
			>
				<Suspense fallback={ <Spinner /> }>{ content }</Suspense>
			</div>
		</div>
	);
};

export default Panel;
