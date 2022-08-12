/**
 * External dependencies
 */
import { Icon, help } from '@wordpress/icons';
import { Panel, PanelBody as PanelBodyBase } from '@wordpress/components';

/**
 * Internal dependencies
 */
import strings from './strings';

const PanelBody: React.FC< PanelBodyBase.Props > = ( props ) => {
	return <PanelBodyBase initialOpen={ false } { ...props } />;
};

const FrequentlyAskedQuestions: React.FC = () => {
	return (
		<div className="faq__card">
			<h3>{ strings.faq.faqHeader }</h3>
			<Panel>
				<PanelBody title={ strings.faq.question1 }>
					<p>{ strings.faq.question1Answer1 }</p>
					<p>{ strings.faq.question1Answer2 }</p>
				</PanelBody>

				<PanelBody title={ strings.faq.question2 }>
					<p>{ strings.faq.question2Answer1 }</p>
				</PanelBody>

				<PanelBody title={ strings.faq.question3 }>
					<ul>
						<li>{ strings.faq.question3Answer1 }</li>
						<li>{ strings.faq.question3Answer2 }</li>
						<li>{ strings.faq.question3Answer3 }</li>
						<li>{ strings.faq.question3Answer4 }</li>
						<li>{ strings.faq.question3Answer5 }</li>
					</ul>
				</PanelBody>

				<PanelBody title={ strings.faq.question4 }>
					<p>{ strings.faq.question4Answer1 }</p>
				</PanelBody>

				<PanelBody title={ strings.faq.question5 }>
					<p>{ strings.faq.question5Answer1 }</p>
					<p>{ strings.faq.question5Answer2 }</p>
				</PanelBody>

				<PanelBody title={ strings.faq.question6 }>
					<p>{ strings.faq.question6Answer1 }</p>
					<p>{ strings.faq.question6Answer2 }</p>
					<p>{ strings.faq.question6Answer3 }</p>
					<p>{ strings.faq.question6Answer4 }</p>
				</PanelBody>

				<PanelBody title={ strings.faq.question7 }>
					<p>{ strings.faq.question7Answer1 }</p>
					<ul>
						<li>{ strings.faq.question7Answer2 }</li>
						<li>{ strings.faq.question7Answer3 }</li>
						<li>{ strings.faq.question7Answer4 }</li>
						<li>{ strings.faq.question7Answer5 }</li>
						<li>{ strings.faq.question7Answer6 }</li>
						<li>{ strings.faq.question7Answer7 }</li>
						<li>{ strings.faq.question7Answer8 }</li>
						<li>{ strings.faq.question7Answer9 }</li>
						<li>{ strings.faq.question7Answer10 }</li>
					</ul>
					<p>{ strings.faq.question7Answer11 }</p>
					<p>{ strings.faq.question7Answer12 }</p>
					<p>{ strings.faq.question7Answer13 }</p>
				</PanelBody>

				<PanelBody title={ strings.faq.question8 }>
					<p>{ strings.faq.question8Answer1 }</p>
					<p>{ strings.faq.question8Answer2 }</p>
				</PanelBody>
			</Panel>
			<div className="help-section">
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

export default FrequentlyAskedQuestions;
