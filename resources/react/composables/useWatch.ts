import { useLatest } from '@laravilt/support/composables/hooks';
import { useEffect, useRef } from 'react';

/**
 * React twin of a non-immediate Vue `watch(source, cb)`: runs `callback(value, previous)` after a render in
 * which `value` changed (Object.is), never on mount. Safe under StrictMode's double effect invocation.
 */
export function useWatch<T>(value: T, callback: (value: T, previous: T) => void): void {
    const previous = useRef<T>(value);
    const latest = useLatest(callback);

    useEffect(() => {
        if (Object.is(previous.current, value)) {
            return;
        }

        const old = previous.current;
        previous.current = value;
        latest.current(value, old);
    }, [value, latest]);
}
