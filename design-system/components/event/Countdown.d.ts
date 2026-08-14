/**
 * @startingPoint section="Event platform" subtitle="Live countdown band to day one" viewport="700x200"
 */
export interface CountdownProps {
  /** ISO 8601 target, e.g. '2027-01-18T09:00:00+05:30'. Include the offset. */
  target: string;
  /** Small uppercase label, e.g. 'Countdown to day one'. */
  eyebrow?: string;
  /** Human-readable date beside the digits. */
  label?: string;
  /** Replaces the digits once the target has passed. */
  finishedText?: string;
  /** dark for navy bands, light for white/tint bands. */
  variant?: 'dark' | 'light';
}

export declare function Countdown(props: CountdownProps): JSX.Element;
