import type { SVGAttributes } from 'react';

/**
 * Refugio del Mar mark: an Andalusian arch framing two waves.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 38V18a15 15 0 0 1 30 0v20h-5V18a10 10 0 0 0-20 0v20z" />
            <path
                d="M12 27.5c2.7-2.4 5.3-2.4 8 0s5.3 2.4 8 0M12 33.5c2.7-2.4 5.3-2.4 8 0s5.3 2.4 8 0"
                fill="none"
                stroke="currentColor"
                strokeWidth="2.5"
                strokeLinecap="round"
            />
        </svg>
    );
}
