export interface FeaturedSpeakerProps {
  name?: string;
  /** Portrait. Rendered grayscale + high contrast, colourising on hover. */
  image?: string;
  imageAlt?: string;
  /** Small uppercase tag over the portrait, e.g. 'Keynote'. */
  badge?: string;
  /** One or two lines: role, organisation, talk title. */
  body?: React.ReactNode;
  href?: string;
}

export declare function FeaturedSpeaker(props: FeaturedSpeakerProps): JSX.Element;
