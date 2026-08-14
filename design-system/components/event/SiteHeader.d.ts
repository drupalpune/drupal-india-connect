export interface SiteHeaderProps {
  /** Logo lockup and site name. Logo renders at 5rem tall by default. */
  branding?: React.ReactNode;
  /** The primary menu region. */
  menu?: React.ReactNode;
  /** Registration CTA label; renders the black slab treatment. */
  registerLabel?: string;
  registerHref?: string;
}

export declare function SiteHeader(props: SiteHeaderProps): JSX.Element;
