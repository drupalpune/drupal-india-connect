export interface ImageProps {
  src: string;
  /** Empty string only when the image is purely decorative. */
  alt?: string;
  ratio?: '2:1' | '16:9' | '3:2' | '4:3' | '1:1';
  radius?: 'small' | 'large' | 'extra-large';
  /** Wrap in a link; adds a 1.05 scale on hover. */
  url?: string;
  caption?: string;
}

export declare function Image(props: ImageProps): JSX.Element;
