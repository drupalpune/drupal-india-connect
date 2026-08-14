import type { SiteHeroDay } from './SiteHeroDetails';

/**
 * @startingPoint section="Event platform" subtitle="Homepage hero: giant type, date tab, 1:1 video" viewport="700x420"
 */
export interface SiteHero25Props {
  /** The event name. Rendered uppercase at up to 13rem with a gradient text fill. */
  eventName?: string;
  description?: React.ReactNode;
  /** Small uppercase link under the title, with the orange arrow chip. */
  ctaLabel?: string;
  ctaHref?: string;
  dates?: string;
  location?: string;
  days?: SiteHeroDay[];
  /** Looping, muted background video for the 1:1 media well. */
  videoSrc?: string;
  videoType?: string;
  /** Poster image; also used alone when there is no video. */
  posterSrc?: string;
  /** Masked graphic that peeks out below the hero. */
  graphicSrc?: string;
  /** Texture image showing through the title's gradient fill. */
  textureSrc?: string;
}

export declare function SiteHero25(props: SiteHero25Props): JSX.Element;
