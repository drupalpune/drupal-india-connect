export interface SponsorTeaserProps {
  /** Organisation name — used as the logo's alt text and the link label. */
  name?: string;
  logo?: string;
  href?: string;
  /** Blurb. Hidden at most tiers; shown for the top tier. */
  body?: React.ReactNode;
}

export declare function SponsorTeaser(props: SponsorTeaserProps): JSX.Element;
