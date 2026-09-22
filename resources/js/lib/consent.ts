import { ref } from 'vue';

/** The key the head script in app.blade.php reads before Google Analytics loads. */
const storageKey = 'analytics-consent';

type Choice = 'granted' | 'denied';

declare global {
    interface Window {
        gtag?: (...args: unknown[]) => void;
    }
}

function storedChoice(): Choice | null {
    try {
        const value = localStorage.getItem(storageKey);

        return value === 'granted' || value === 'denied' ? value : null;
    } catch {
        return null;
    }
}

/**
 * Whether the cookie banner is showing. Module level, so the footer link that
 * reopens it and the banner itself share one state.
 */
export const consentOpen = ref(false);

/** Show the banner if the reader has never answered. */
export function askIfUnanswered(): void {
    consentOpen.value = storedChoice() === null;
}

/**
 * Store the answer and tell Google Analytics. Withdrawing consent also removes
 * the cookies it has already set, so a no means no.
 */
export function choose(choice: Choice): void {
    try {
        localStorage.setItem(storageKey, choice);
    } catch {
        // without storage the answer holds for this page only
    }

    window.gtag?.('consent', 'update', { analytics_storage: choice });

    if (choice === 'denied') {
        const host = location.hostname.replace(/^www\./, '');

        for (const cookie of document.cookie.split(';')) {
            const name = cookie.split('=')[0].trim();

            if (name.startsWith('_ga')) {
                for (const domain of ['', `; domain=.${host}`]) {
                    document.cookie = `${name}=; max-age=0; path=/${domain}`;
                }
            }
        }
    }

    consentOpen.value = false;
}
