/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { Notice } from 'wordpress-components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './style.scss';
import { useStoreNoticesContext } from '../context';

const getWooClassName = ( { status = 'default' } ) => {
	switch ( status ) {
		case 'error':
			return 'woocommerce-error';
		case 'success':
			return 'woocommerce-message';
		case 'info':
		case 'warning':
			return 'woocommerce-info';
	}
	return '';
};

export const StoreNoticesContainer = ( {
	className,
	context = 'default',
	additionalNotices = [],
} ) => {
	const { isSuppressed } = useStoreNoticesContext();

	const { notices } = useSelect( ( select ) => {
		const store = select( 'core/notices' );
		return {
			notices: store.getNotices( context ),
		};
	} );
	const { removeNotice } = useDispatch( 'core/notices' );
	const regularNotices = notices
		.filter( ( notice ) => notice.type !== 'snackbar' )
		.concat( additionalNotices );

	if ( ! regularNotices.length ) {
		return null;
	}

	const wrapperClass = classnames( className, 'wc-block-components-notices' );

	return isSuppressed ? null : (
		<div className={ wrapperClass }>
			{ regularNotices.map( ( props ) => (
				<Notice
					key={ 'store-notice-' + props.id }
					{ ...props }
					className={ classnames(
						'wc-block-components-notices__notice',
						getWooClassName( props )
					) }
					onRemove={ () => {
						if ( props.isDismissible ) {
							removeNotice( props.id, context );
						}
					} }
				>
					{ props.content }
				</Notice>
			) ) }
		</div>
	);
};

StoreNoticesContainer.propTypes = {
	className: PropTypes.string,
	notices: PropTypes.arrayOf(
		PropTypes.shape( {
			content: PropTypes.string.isRequired,
			id: PropTypes.string.isRequired,
			status: PropTypes.string.isRequired,
			isDismissible: PropTypes.bool,
			type: PropTypes.oneOf( [ 'default', 'snackbar' ] ),
		} )
	),
};
