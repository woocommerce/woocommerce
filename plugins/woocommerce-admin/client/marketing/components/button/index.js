/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

export default ( props ) => {
	return (
		<Button
			{ ...props }
			className={ classnames(
				props.className,
				'woocommere-admin-marketing-button'
			) }
		/>
	);
};
