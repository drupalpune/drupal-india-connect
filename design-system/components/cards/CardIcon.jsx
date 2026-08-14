import React from 'react';
import { Icon } from '../base/Icon.jsx';

export function CardIcon({
  icon,
  iconSize = 'large',
  iconStyle,
  iconAlign = 'center',
  text,
  description,
  url,
  backgroundColor,
  borderRadius,
  tileSize,
  textAlign = 'center',
}) {
  const classes = [
    'eh-card-icon',
    backgroundColor ? `eh-card-icon--bg-${backgroundColor}` : null,
    borderRadius ? `eh-card-icon--radius-${borderRadius}` : null,
    tileSize ? `eh-card-icon--size-${tileSize.replace('4:3', '4-3').replace('16:9', '16-9')}` : null,
    url ? 'eh-card-icon--linked' : null,
  ].filter(Boolean).join(' ');

  const body = (
    <>
      {text && <h3 className="eh-card-icon__heading">{text}</h3>}
      {description && <div className="eh-card-icon__description">{description}</div>}
    </>
  );

  return (
    <div className={classes}>
      <div className="eh-card-icon__inner">
        {icon && (
          <div className={`eh-card-icon__icon-wrap eh-card-icon__icon-align-${iconAlign}`}>
            <div className={['eh-card-icon__icon-inner', iconStyle ? `eh-card-icon__icon-inner--${iconStyle}` : null].filter(Boolean).join(' ')}>
              <Icon icon={icon} size={iconSize} />
            </div>
          </div>
        )}
        {(text || description) && (
          <div className={`eh-card-icon__content eh-card-icon__content--align-${textAlign}`}>
            {url ? <a href={url} className="eh-card-icon__link">{body}</a> : body}
          </div>
        )}
      </div>
    </div>
  );
}
