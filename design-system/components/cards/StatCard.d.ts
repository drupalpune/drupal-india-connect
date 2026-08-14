export interface StatCardProps {
  /** The figure. Short — '800', '60+', '12'. Rendered at 56px/800. */
  stat?: string;
  /** What it counts. Sentence case, no trailing period. */
  description?: React.ReactNode;
}

export declare function StatCard(props: StatCardProps): JSX.Element;
