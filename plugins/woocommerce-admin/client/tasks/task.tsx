export type TaskProps = {
	query: { task: string };
};

export const Task: React.FC< TaskProps > = ( { query } ) => (
	<div>Task: { query.task }</div>
);
