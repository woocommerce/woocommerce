import { useState } from 'react';
import { Button, Icon } from '@wordpress/components';
import { closeSmall, tip } from '@wordpress/icons';

export default function SuggestedRatesNotice() {
  const [isVisible, setIsVisible] = useState(true);

  if (!isVisible) {
    return null;
  }

  return (
    <aside className="shipping-suggested-rates-notice" role="note">
      <Icon
        icon={tip}
        size={24}
        className="shipping-suggested-rates-notice__icon"
      />
      <div className="shipping-suggested-rates-notice__content">
        <h3>Starting rates based on stores like yours</h3>
        <p>
          AI suggested these shipping rates from industry references while you
          build up order history. Change anything in the shipping hub whenever
          you like.
        </p>
      </div>
      <Button
        className="shipping-suggested-rates-notice__dismiss"
        icon={closeSmall}
        label="Dismiss notice"
        onClick={() => setIsVisible(false)}
        variant="tertiary"
        __next40pxDefaultSize
      />
    </aside>
  );
}
