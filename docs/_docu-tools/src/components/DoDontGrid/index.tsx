import type {ReactNode} from 'react';
import styles from './styles.module.css';

type DoDontGridProps = {
  children: ReactNode;
};

type PanelProps = {
  children: ReactNode;
};

type PanelType = 'do' | 'dont';

type DoDontGridComponent = ((props: DoDontGridProps) => ReactNode) & {
  Do: (props: PanelProps) => ReactNode;
  Dont: (props: PanelProps) => ReactNode;
};

function Panel({
  children,
  type,
}: PanelProps & {type: PanelType}): ReactNode {
  const panelClass =
    type === 'do'
      ? `${styles.panel} ${styles.panelDo}`
      : `${styles.panel} ${styles.panelDont}`;

  return (
    <section className={panelClass}>
      <div className={styles.content}>{children}</div>
    </section>
  );
}

const DoDontGrid = (({children}: DoDontGridProps): ReactNode => {
  return <div className={styles.grid}>{children}</div>;
}) as DoDontGridComponent;

DoDontGrid.Do = ({children}: PanelProps): ReactNode => {
  return <Panel type="do">{children}</Panel>;
};

DoDontGrid.Dont = ({children}: PanelProps): ReactNode => {
  return <Panel type="dont">{children}</Panel>;
};

export default DoDontGrid;
