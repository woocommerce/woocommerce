/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import classNames from 'classnames';
import PropTypes from 'prop-types';
import { Icon, chevronUp, chevronDown } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';

const Panel = ( {
	children,
	className,
	initialOpen = false,
	title,
	titleTag: TitleTag = 'div',
} ) => {
	const [ isOpen, setIsOpen ] = useState( initialOpen );

	return (
		<div
			className={ classNames( className, 'wc-blocks-components-panel' ) }
		>
			<TitleTag>
				<button
					aria-expanded={ isOpen }
					className="wc-blocks-components-panel__button"
					onClick={ () => setIsOpen( ! isOpen ) }
				>
					<Icon
						aria-hidden="true"
						className="wc-blocks-components-panel__button-icon"
						srcElement={ isOpen ? chevronUp : chevronDown }
					/>
					{ title }
				</button>
			</TitleTag>
			<div
				className="wc-blocks-components-panel__content"
				hidden={ ! isOpen }
			>
				{ children }
			</div>
		</div>
	);
};

Panel.propTypes = {
	className: PropTypes.string,
	initialOpen: PropTypes.bool,
	title: PropTypes.element,
	titleTag: PropTypes.string,
};

export default Panel;
