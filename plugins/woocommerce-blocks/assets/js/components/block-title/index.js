/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { PlainText } from '@wordpress/block-editor';
import classnames from 'classnames';

const BlockTitle = ( { className, headingLevel, onChange, heading } ) => {
	const TagName = `h${ headingLevel }`;
	return (
		<TagName>
			<PlainText
				className={ classnames(
					'wc-block-component-title',
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
	 * The value of the heading
	 */
	value: PropTypes.string,
	/**
	 * Callback to update the attribute when text is changed
	 */
	onChange: PropTypes.func,
	/**
	 * Callback to update the attribute when text is changed
	 */
	headingLevel: PropTypes.func,
};

export default BlockTitle;
