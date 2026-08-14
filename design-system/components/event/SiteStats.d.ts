export interface SiteStat {
  /** The figure. */
  stat: string;
  /** What it counts. */
  desc: string;
}

export interface SiteStatsProps {
  /** Small uppercase label with the primary rule, e.g. 'Highlights from 2026'. */
  title?: string;
  stats?: SiteStat[];
}

export declare function SiteStats(props: SiteStatsProps): JSX.Element;
