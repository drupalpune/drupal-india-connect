export interface BlockquoteProps {
  /** The quotation, without surrounding quote marks. */
  quote: string;
  /** Who said it. */
  name?: string;
  /** Their role or organisation. */
  cite?: string;
  citeUrl?: string;
  /** The oversized watermark quote glyph. */
  showMark?: boolean;
}

export declare function Blockquote(props: BlockquoteProps): JSX.Element;
