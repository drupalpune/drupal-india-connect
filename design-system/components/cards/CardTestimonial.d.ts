export interface CardTestimonialProps {
  /** The quote, without quote marks. */
  text?: string;
  citeName?: string;
  /** Role and organisation. */
  citeText?: string;
  citeUrl?: string;
  /** Avatar image — rendered as a 60px circle. */
  media?: string;
  mediaAlt?: string;
  align?: 'center' | 'left';
  style?: 'default' | 'inverted';
}

export declare function CardTestimonial(props: CardTestimonialProps): JSX.Element;
