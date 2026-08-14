export interface HeadingProps {
  text: string;
  /** Semantic level. Pick for document structure, then set size separately. */
  level?: 1 | 2 | 3 | 4 | 5 | 6;
  /** Responsive size step. Omit to inherit the base element size. */
  size?: '8xl' | '7xl' | '6xl' | '5xl' | '4xl' | '3xl' | '2xl' | 'xl' | 'lg' | 'sm' | 'xs';
  color?: 'default' | 'inverted' | 'primary' | 'muted' | 'accent';
  align?: 'left' | 'center' | 'right';
  /** 'accented' adds the 4px primary rule and uppercase tracking. */
  style?: 'default' | 'accented';
  marginTop?: 'sm' | 'md' | 'lg' | 'xl';
  url?: string;
}

export declare function Heading(props: HeadingProps): JSX.Element;
