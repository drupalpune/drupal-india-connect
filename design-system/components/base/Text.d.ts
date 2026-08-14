export interface TextProps {
  /** Body content as React children. */
  children?: React.ReactNode;
  /** Rich-text HTML string (the Drupal path). Use instead of children. */
  html?: string;
  size?: 'xs' | 'sm' | 'normal' | 'lg' | 'xl' | '2xl' | '3xl';
  color?: 'default' | 'inverted' | 'primary';
}

export declare function Text(props: TextProps): JSX.Element;
