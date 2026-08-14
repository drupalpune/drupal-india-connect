/**
 * @startingPoint section="Layout" subtitle="Banded, grid-aware page section" viewport="700x260"
 */
export interface SectionProps {
  children?: React.ReactNode;
  /** Optional header area above the grid (eyebrow + heading). */
  header?: React.ReactNode;
  footer?: React.ReactNode;
  /** Column split at >=700px. One column below that, always. */
  columns?: '100' | '50-50' | '33-33-33' | '75-25' | '25-75' | '67-33' | '33-67'
    | '50-25-25' | '25-50-25' | '25-25-50' | '25-25-25-25';
  mobileColumns?: '1' | '2' | '3';
  gridAlign?: 'stretch' | 'start' | 'center' | 'end';
  backgroundColor?: 'primary' | 'secondary' | 'accent' | 'muted' | 'black';
  backgroundImage?: string;
  /** Flips headings, text and stat cards to their on-dark colours. */
  darkBackground?: boolean;
  /** Bleeds the background colour to the viewport edge. */
  fullWidth?: boolean;
  width?: '100%' | '90%' | '80%' | '75%' | '50%';
  marginBlockStart?: '0' | '8' | '20' | '32' | '48' | '64' | '96' | '128';
  marginBlockEnd?: '0' | '8' | '20' | '32' | '48' | '64' | '96' | '128';
  paddingBlockStart?: '0' | '16' | '32' | '64';
  paddingBlockEnd?: '0' | '16' | '32' | '64';
}

export declare function Section(props: SectionProps): JSX.Element;
