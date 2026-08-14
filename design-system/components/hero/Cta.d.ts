/**
 * @startingPoint section="Hero" subtitle="Full-band call to action" viewport="700x300"
 */
export interface CtaProps {
  headingText?: string;
  text?: string;
  /** One or two Buttons. */
  actions?: React.ReactNode;
  level?: 2 | 3 | 4;
  textAlign?: 'left' | 'center' | 'right';
  backgroundColor?: 'primary' | 'secondary' | 'accent' | 'muted' | 'inverted';
  media?: string;
  mediaAlt?: string;
  overlayOpacity?: '0%' | '20%' | '40%' | '60%' | '75%';
}

export declare function Cta(props: CtaProps): JSX.Element;
