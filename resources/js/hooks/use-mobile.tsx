import { useSyncExternalStore } from 'react';

const MOBILE_QUERY = '(max-width: 767px)';

const subscribe = (onChange: () => void) => {
    const mql = window.matchMedia(MOBILE_QUERY);

    mql.addEventListener('change', onChange);

    return () => mql.removeEventListener('change', onChange);
};

export function useIsMobile(): boolean {
    return useSyncExternalStore(
        subscribe,
        () => window.matchMedia(MOBILE_QUERY).matches,
        () => false,
    );
}
