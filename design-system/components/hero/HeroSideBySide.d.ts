/**
 * @startingPoint section="Hero" subtitle="Image beside copy, stacks on mobile" viewport="700x340"
 */
export interface HeroSideBySideProps {
  children?: React.ReactNode;
  media?: string;
  mediaAlt?: string;
  imagePosition?: 'left' | 'right';
  imageSize?: '2:1' | '16:9' | '3:2' | '4:3' | '1:1';
  imageRadius?: 'small' | 'large' | 'extra-large';
  gap?: 'large' | 'extra-large';
  /** vertical-reverse puts the copy above the image on mobile. */
  mobileDirection?: 'vertical' | 'vertical-reverse';
  justify?: 'start' | 'center' | 'end';
  paddingBlockStart?: '0' | '16' | '32' | '64';
  paddingBlockEnd?: '0' | '16' | '32' | '64';
}

export declare function HeroSideBySide(props: HeroSideBySideProps): JSX.Element;
