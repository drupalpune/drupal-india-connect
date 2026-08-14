/**
 * @startingPoint section="Hero" subtitle="Full-bleed image hero with overlaid copy" viewport="700x400"
 */
export interface HeroBillboardProps {
  /** Heading, copy and buttons. */
  children?: React.ReactNode;
  /** Background image URL. */
  media?: string;
  mediaAlt?: string;
  /** White scrim over the image, for text legibility. */
  overlayOpacity?: '0%' | '20%' | '40%' | '60%' | '75%';
  objectPosition?: 'top' | 'center' | 'bottom';
  /** full = 100dvh; large ≈ 75vh; ribbon ≈ 50vh. */
  height?: 'full' | 'large' | 'ribbon';
  flexPosition?: 'top-left' | 'center-left' | 'bottom-left' | 'hero-center';
  /** Pulls the hero up behind a transparent navbar. */
  overlapNavbar?: boolean;
}

export declare function HeroBillboard(props: HeroBillboardProps): JSX.Element;
