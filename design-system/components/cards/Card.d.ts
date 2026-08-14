/**
 * @startingPoint section="Cards" subtitle="Media card, framed or full-bleed" viewport="700x300"
 */
export interface CardProps {
  headingText?: string;
  text?: string;
  /** Image URL. Rendered 16:9 when vertical, half-width when horizontal. */
  media?: string;
  mediaAlt?: string;
  /** Makes the whole card clickable via a stretched link. */
  url?: string;
  orientation?: 'vertical' | 'horizontal';
  /** framed = shadow + hairline border; full = edge-to-edge media. */
  style?: 'framed' | 'full';
  background?: 'default' | 'background' | 'accent' | 'primary' | 'inverted';
  centered?: boolean;
  level?: 2 | 3 | 4 | 5 | 6;
}

export declare function Card(props: CardProps): JSX.Element;
