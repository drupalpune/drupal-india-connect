export interface NavbarItem {
  label: string;
  href: string;
  active?: boolean;
}

/**
 * @startingPoint section="Layout" subtitle="Site navigation bar with logo and CTA" viewport="700x120"
 */
export interface NavbarProps {
  /** The logo lockup — usually an <img> or <a><img/></a>. Capped at 2rem tall. */
  logo?: React.ReactNode;
  items?: NavbarItem[];
  /** Buttons on the right, e.g. the ticket CTA. */
  actions?: React.ReactNode;
  menuAlign?: 'left' | 'center' | 'right';
}

export declare function Navbar(props: NavbarProps): JSX.Element;
