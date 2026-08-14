export interface AccordionProps {
  /** The question or row label. */
  title: string;
  children?: React.ReactNode;
  headingLevel?: 2 | 3 | 4 | 5 | 6;
  /** Open the first row of an FAQ; leave the rest closed. */
  openByDefault?: boolean;
}

export declare function Accordion(props: AccordionProps): JSX.Element;
