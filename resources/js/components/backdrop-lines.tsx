import { useEffect, useRef, useState } from "react"

type Props = {
	animated?: boolean
}

const LINE_GAP = 12
const LINES_PER_BAR = 3

// Deterministic 0–1 noise per bar index, so the layout is stable across
// renders and resizes but neighbouring bars still look unrelated.
function noise(index: number, salt: number) {
	const x = Math.sin(index * 12.9898 + salt * 78.233) * 43758.5453
	return x - Math.floor(x)
}

// Each bar rises from the bottom along one of the background's vertical lines
// (12px apart) so it stays perfectly aligned with the grid. Bars are generated
// from the measured width so they span the whole backdrop on any screen.
// Negative delays start every bar mid-bounce, so the page never opens on a
// flat floor.
function buildBars(width: number) {
	const count = Math.ceil(width / (LINE_GAP * LINES_PER_BAR))

	return Array.from({ length: count }, (_, index) => ({
		left: index * LINES_PER_BAR * LINE_GAP,
		height: 30 + Math.round(noise(index, 1) * 34),
		delay: -(noise(index, 2) * 1.8),
		duration: 1.2 + noise(index, 3) * 0.7,
		pattern: noise(index, 4) < 0.5 ? "a" : "b",
	}))
}

export function BackdropLines({ animated = true }: Props) {
	const containerRef = useRef<HTMLDivElement>(null)
	const [width, setWidth] = useState(0)

	useEffect(() => {
		const container = containerRef.current

		if (!animated || !container) {
			return
		}

		const observer = new ResizeObserver(([entry]) => {
			setWidth(entry.contentRect.width)
		})

		observer.observe(container)

		return () => observer.disconnect()
	}, [animated])

	return (
		<div
			ref={containerRef}
			className="pointer-events-none absolute inset-0 z-0 overflow-hidden">
			<div className="absolute inset-0 bg-[repeating-linear-gradient(90deg,color-mix(in_oklch,var(--primary)_10%,transparent)_0_1px,transparent_1px_12px)] opacity-[0.5] dark:bg-[repeating-linear-gradient(90deg,color-mix(in_oklch,var(--primary)_18%,transparent)_0_1px,transparent_1px_12px)] dark:opacity-[0.16]" />
			{animated && (
				<div className="eq-bars absolute inset-0">
					{buildBars(width).map((bar, index) => (
						<span
							key={index}
							className={`eq-bar eq-bar-${bar.pattern}`}
							style={{
								left: `${bar.left}px`,
								height: `${bar.height}%`,
								animationDelay: `${bar.delay}s`,
								animationDuration: `${bar.duration}s`,
							}}
						/>
					))}
				</div>
			)}
		</div>
	)
}
