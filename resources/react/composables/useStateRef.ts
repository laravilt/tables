import { useCallback, useRef, useState, type RefObject } from 'react';

/**
 * State that is also readable synchronously through a ref — the React equivalent of a Vue `ref()` that is
 * written in a handler and read right away (or later from a timeout/observer) in the same tick.
 * The setter updates the ref immediately and schedules the re-render.
 */
export function useStateRef<T>(initial: T | (() => T)): [T, (value: T) => void, RefObject<T>] {
    const [state, setState] = useState<T>(initial);
    const ref = useRef<T>(state);

    const set = useCallback((value: T) => {
        ref.current = value;
        setState(value);
    }, []);

    return [state, set, ref];
}
