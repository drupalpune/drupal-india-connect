import type { IconName } from './Icon';

/**
 * @startingPoint section="Core" subtitle="Filled, tinted, inverted and outlined buttons" viewport="700x150"
 */
export interface ButtonProps {
  /** Button text. Sentence case, verb-led — "Get event updates", not "Submit". */
  label: string;
  /** Renders an <a> instead of a <button>. */
  href?: string;
  variant?: 'primary' | 'secondary' | 'primary-inverted' | 'secondary-inverted';
  size?: 'small' | 'medium' | 'large';
  /** Optional trailing glyph; arrow-right is the house choice. */
  icon?: IconName;
  /** Put the glyph before the label. */
  iconFirst?: boolean;
  /** Full width below 500px, auto above. */
  fullWidthMobile?: boolean;
  disabled?: boolean;
  onClick?: (event: React.MouseEvent) => void;
}

export declare function Button(props: ButtonProps): JSX.Element;
