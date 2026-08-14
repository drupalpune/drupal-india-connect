export interface AnnouncementBarProps {
  /** The headline fact. Keep it to one short clause. */
  message: string;
  /** Secondary clause, lighter weight. */
  detail?: string;
  linkUrl?: string;
  linkText?: string;
  variant?: 'secondary' | 'primary' | 'dark';
}

export declare function AnnouncementBar(props: AnnouncementBarProps): JSX.Element;
