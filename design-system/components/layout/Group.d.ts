export interface GroupProps {
  children?: React.ReactNode;
  direction?: 'column' | 'row';
  itemsAlign?: 'start' | 'center' | 'end';
  selfAlign?: 'start' | 'center' | 'end';
  gap?: 'sm' | 'md' | 'lg' | 'xl' | 'grid';
  /** Every direct child flexes to an equal share. */
  equalChildren?: boolean;
  radius?: 'sm' | 'md' | 'lg' | 'xl';
  padding?: 'sm' | 'md' | 'lg' | 'xl';
  background?: 'primary' | 'secondary' | 'accent';
  /** Entrance animation. Use sparingly — one per section at most. */
  animation?: 'fade_up' | 'fade_down' | 'fade_left' | 'fade_right';
  /** CSS time, e.g. '0.2s'. */
  animationDelay?: string;
  animationDuration?: string;
}

export declare function Group(props: GroupProps): JSX.Element;
