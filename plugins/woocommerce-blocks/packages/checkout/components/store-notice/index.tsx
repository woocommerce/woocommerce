/**
 * External dependencies
 */
import classnames from 'classnames';
import { Notice } from 'wordpress-components';
import { info, warning, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

const StoreNotice = ( { className, children, status, ...props } ) => {
	return (
		<Notice
			className={ classnames( 'wc-block-store-notice', className ) }
			{ ...props }
			status={ status }
		>
			<Icon icon={ status === 'error' ? warning : info } />
			<div>{ children }</div>
		</Notice>
	);
};

export default StoreNotice;
