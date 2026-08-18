/**
 * @startingPoint section="Cards" subtitle="Sponsorship tier: price and a benefits checklist" viewport="360x520"
 */
export interface SponsorTierProps {
  tierName?: string;
  /** A string, not a number — "Price on request" works as well as a currency amount. */
  price?: string;
  /** Rich-text HTML, expected to be a <ul>/<li> checklist. Each item gets a check mark via CSS. */
  benefits?: string;
  /** Spend "secondary" on at most one tier per page — it's the brand's rationed accent. */
  tierAccent?: 'secondary' | 'primary-50' | 'primary-60' | 'neutral';
  ctaText?: string;
  /** Omit to end the card after the last benefit — no CTA renders without a URL. */
  ctaUrl?: string;
}

export declare function SponsorTier(props: SponsorTierProps): JSX.Element;
