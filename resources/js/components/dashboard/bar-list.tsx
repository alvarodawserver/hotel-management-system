/**
 * A ranked list with a horizontal bar per row, scaled to the largest value.
 * The figure is written next to each bar, so nothing depends on reading the
 * bar length alone.
 */
export function BarList({
    rows,
}: {
    rows: { label: string; value: number; display: string }[];
}) {
    const max = Math.max(1, ...rows.map((row) => row.value));

    return (
        <ul className="space-y-3">
            {rows.map((row) => (
                <li key={row.label}>
                    <div className="flex items-baseline justify-between gap-3 text-sm">
                        <span className="truncate font-medium">
                            {row.label}
                        </span>
                        <span className="shrink-0 text-muted-foreground tabular-nums">
                            {row.display}
                        </span>
                    </div>
                    <div className="mt-1.5 h-2 rounded-full bg-muted">
                        <div
                            className="h-full rounded-full bg-chart-1"
                            style={{ width: `${(row.value / max) * 100}%` }}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}
