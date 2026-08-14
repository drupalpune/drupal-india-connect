export interface VideoProps {
  src: string;
  poster?: string;
  /** Doubles as the video's aria-label. */
  caption?: string;
  /** Autoplaying video is always muted and should always loop. */
  autoplay?: boolean;
  loop?: boolean;
  muted?: boolean;
  /** The theme's own overlaid play/pause control (bottom right). */
  controls?: boolean;
}

export declare function Video(props: VideoProps): JSX.Element;
