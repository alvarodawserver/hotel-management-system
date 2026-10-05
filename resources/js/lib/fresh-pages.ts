import { router } from '@inertiajs/react';

/**
 * Public pages whose data changes because of what other users (or the
 * management area) do: hotel counts, availability, reservation status…
 */
const LIVE_PAGES = ['welcome', 'catalog/', 'reservations/'];

const isLivePage = (component: string) =>
    LIVE_PAGES.some((name) =>
        name.endsWith('/') ? component.startsWith(name) : component === name,
    );

/**
 * Keep Inertia's client-side copies of pages from showing stale data:
 * - any change made through a form (publishing a hotel, booking…) clears
 *   the prefetch cache, so a link hovered earlier is not served from it;
 * - going back or forward in the browser to a live page restores it
 *   instantly from history and then refreshes its props in the background.
 */
export function keepPagesFresh(): void {
    let restoringFromHistory = false;

    // Inertia pages carry their state; anchor jumps (#rooms) push none.
    window.addEventListener('popstate', (event) => {
        restoringFromHistory = event.state !== null;
    });

    router.on('navigate', (event) => {
        if (!restoringFromHistory) {
            return;
        }

        restoringFromHistory = false;

        if (isLivePage(event.detail.page.component)) {
            router.reload();
        }
    });

    router.on('finish', (event) => {
        const { visit } = event.detail;

        if (visit.completed && visit.method !== 'get') {
            router.flushAll();
        }
    });
}
