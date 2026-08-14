export interface CardLogoProps {
  /** Logo image URL. Padded 10% inside the tile. */
  media?: string;
  /** The organisation's name — never leave this empty on a real logo. */
  mediaAlt?: string;
  url?: string;
  backgroundColor?: 'primary' | 'secondary' | 'accent' | 'inverted';
  borderRadius?: 'small' | 'medium' | 'large';
}

export declare function CardLogo(props: CardLogoProps): JSX.Element;
