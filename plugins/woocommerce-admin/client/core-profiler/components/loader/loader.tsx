/**
 * External dependencies
 */
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import { CoreProfilerStateMachineContext } from '../..';
import LightBulb from './images/lightbulb';
import ProgressBar from '../progress-bar/progress-bar';

type Props = {
	title: string | JSX.Element;
	progress?: number;
	className?: string;
	paragraphs?: Array< {
		text: string | JSX.Element;
		duration?: number;
	} >;
};

export const Loader = ( {
	context,
}: {
	context: CoreProfilerStateMachineContext;
} ) => {
	const stage = context.loader.stages[ context.loader.currentStage ?? 0 ];
	return (
		<div
			className={ classNames(
				'woocommerce-profiler-loader',
				stage.className
			) }
		>
			{ stage.image ?? <LightBulb /> }

			<h1 className="woocommerce-profiler-loader__title">
				{ stage.title }
			</h1>
			<ProgressBar
				className={ 'progress-bar' }
				percent={ stage.progress }
				color={ 'var(--wp-admin-theme-color)' }
				bgcolor={ '#E0E0E0' }
			/>
		</div>
	);
};
