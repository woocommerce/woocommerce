/**
 * External dependencies
 */
import { Icon, help } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import strings from './strings';

const FrequentlyAskedQuestionsSimple: React.FC = () => {
	return (
		<div className="faq__card">
			<div className="help-section-simple">
				<Icon icon={ help } />
				<span>{ strings.faq.haveMoreQuestions }</span>
				<a
					href="https://www.woocommerce.com/my-account/tickets/"
					target="_blank"
					rel="noreferrer"
				>
					{ strings.faq.getInTouch }
				</a>
			</div>
		</div>
	);
};

export default FrequentlyAskedQuestionsSimple;
