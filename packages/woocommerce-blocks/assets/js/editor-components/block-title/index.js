/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { PlainText } from '@wordpress/block-editor';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './editor.scss';

const BlockTitle = ( { className, headingLevel, onChange, heading } ) => {
	const TagName = `h${ headingLevel }`;
	return (
		<TagName>
			<PlainText
				className={ classnames(
					'wc-block-editor-components-title',
					className
				) }
				value={ heading }
				onChange={ onChange }
			/>
		</TagName>
	);
};

BlockTitle.propTypes = {
	/**
	 * Classname to add to title in addition to the defaults.
	 */
	className: PropTypes.string,
	/**
	 * The value of the heading.
	 */
	value: PropTypes.string,
	/**
	 * Callback to update the attribute when text is changed.
	 */
	onChange: PropTypes.func,
	/**
	 * Level of the heading tag (1, 2, 3... will render <h1>, <h2>, <h3>... elements).
	 */
	headingLevel: PropTypes.number,
};

export default BlockTitle;
