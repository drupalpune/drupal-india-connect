import type { IconName } from '../base/Icon';

export interface CardIconProps {
  icon?: IconName;
  iconSize?: 'extra-small' | 'small' | 'medium' | 'large' | 'extra-large';
  /** Tinted plate behind the glyph. */
  iconStyle?: 'primary' | 'primary-background' | 'secondary-background';
  iconAlign?: 'left' | 'center' | 'right';
  /** Card heading. */
  text?: string;
  description?: React.ReactNode;
  url?: string;
  backgroundColor?: 'primary' | 'secondary' | 'accent' | 'muted' | 'background';
  borderRadius?: 'small' | 'medium' | 'large';
  /** Locks the card to a fixed aspect ratio. */
  tileSize?: 'square' | '4:3' | '16:9';
  textAlign?: 'left' | 'center' | 'right';
}

export declare function CardIcon(props: CardIconProps): JSX.Element;
