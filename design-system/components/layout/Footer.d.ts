/**
 * @startingPoint section="Layout" subtitle="Two-row site footer with utility bar" viewport="700x280"
 */
export interface FooterProps {
  /** Logo, blurb and social links. Images are capped at 2rem tall. */
  brand?: React.ReactNode;
  /** Right side of the top row — newsletter or ticket CTA. */
  actions?: React.ReactNode;
  /** Bottom-left utility links. */
  utilityStart?: React.ReactNode;
  /** Bottom-right, right-aligned above 700px — copyright, credits. */
  utilityEnd?: React.ReactNode;
  /** Lay the utility links out as a horizontal row. */
  horizontal?: boolean;
}

export declare function Footer(props: FooterProps): JSX.Element;
