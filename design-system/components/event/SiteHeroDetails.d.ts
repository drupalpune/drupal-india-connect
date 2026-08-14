export interface SiteHeroDay {
  /** e.g. 'Day one'. */
  label?: string;
  /** What happens that day. */
  info?: string;
}

export interface SiteHeroDetailsProps {
  /** Formatted date range, e.g. '18–20 January 2027'. */
  dates?: string;
  /** Short location, e.g. 'Bengaluru, India'. */
  location?: string;
  /** Programme rows in the flyout. */
  days?: SiteHeroDay[];
}

export declare function SiteHeroDetails(props: SiteHeroDetailsProps): JSX.Element;
