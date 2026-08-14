import type { IconName } from './Icon';

export interface BadgeProps {
  label: string;
  /** primary = blue fill; secondary = pale sky fill with dark text. */
  style?: 'primary' | 'secondary';
  icon?: IconName;
  iconFirst?: boolean;
  /** Renders as a link. */
  url?: string;
}

export declare function Badge(props: BadgeProps): JSX.Element;
