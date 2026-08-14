export type IconName =
  | 'arrow-right' | 'arrow-left' | 'caret-right' | 'caret-left' | 'download'
  | 'user-plus' | 'rocket' | 'play-circle' | 'pause-circle' | 'star' | 'check'
  | 'x' | 'calendar' | 'search' | 'mail' | 'phone' | 'map-pin' | 'external-link';

export interface IconProps {
  /** Which glyph to render. The set is fixed — 18 Phosphor icons. */
  icon?: IconName;
  /** extra-small 20px, small 24px, medium 32px, large 48px, extra-large 64px. */
  size?: 'extra-small' | 'small' | 'medium' | 'large' | 'extra-large';
}

export declare function Icon(props: IconProps): JSX.Element | null;
export declare const ICON_PATHS: Record<IconName, string>;
