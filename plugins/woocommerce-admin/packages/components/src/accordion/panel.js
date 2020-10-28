/**
 * External dependencies
 */
import { Card, PanelBody, PanelRow } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * `AccordionPanel` is used to give the panel content an accessible wrapper.
 *
 * @param {Object} props
 * @param {string} props.className
 * @param {string} props.count
 * @param {string} props.children
 * @param {string} props.title
 * @param {string} props.initialOpen
 * @return {Object} -
 */
const AccordionPanel = ( {
	className,
	count,
	title,
	initialOpen,
	children,
} ) => {
	const getTitleAndCount = ( titleText, countUnread ) => {
		return (
			<span className="woocommerce-accordion-header">
				<span className="woocommerce-accordion-title">
					{ titleText }
				</span>
				{ countUnread !== null && (
					<span className="woocommerce-accordion-badge">
						{ countUnread }
					</span>
				) }
			</span>
		);
	};
	return (
		<Card
			size="large"
			className={ classnames( className, 'woocommerce-accordion-card' ) }
		>
			<PanelBody
				title={ getTitleAndCount( title, count ) }
				initialOpen={ initialOpen }
			>
				<PanelRow> { children } </PanelRow>
			</PanelBody>
		</Card>
	);
};

AccordionPanel.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Number of unread elements in the panel that will be shown next to the panel's title.
	 */
	count: PropTypes.number,
	/**
	 * The panel title.
	 */
	title: PropTypes.string,
	/**
	 * Whether or not the panel will start open.
	 */
	initialOpen: PropTypes.bool,
};

AccordionPanel.defaultProps = {
	initialOpen: true,
};

export default AccordionPanel;
