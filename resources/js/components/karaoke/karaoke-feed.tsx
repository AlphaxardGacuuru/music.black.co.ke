import { useCallback, useEffect, useRef, useState, type ReactNode } from "react"
import { KaraokeSlide } from "@/components/karaoke/karaoke-slide"
import { Skeleton } from "@/components/ui/skeleton"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import type { Karaoke } from "@/types/karaoke"

type Props = {
	karaokes: Karaoke[]
	isPending: boolean
	/** Start on this karaoke instead of the first one. */
	initialId?: string
	/** Floats over the top of the feed (tabs, close button, ...). */
	topBar?: ReactNode
	/** Shown when there is nothing to play. */
	empty?: ReactNode
}

/**
 * The full-height karaoke feed: one karaoke per screen, snap-scrolled. The
 * karaoke in view plays (muted until the viewer taps it), the rest wait.
 * Phones get the whole screen (under the bottom nav), larger screens a
 * phone-shaped column.
 */
export function KaraokeFeed({
	karaokes,
	isPending,
	initialId,
	topBar,
	empty,
}: Props) {
	const player = useAudioPlayer()
	const scroller = useRef<HTMLDivElement>(null)
	const [activeIndex, setActiveIndex] = useState(0)
	const [muted, setMuted] = useState(true)
	const hasScrolledToInitial = useRef(false)

	// Track which slide fills the screen.
	useEffect(() => {
		const root = scroller.current

		if (!root || karaokes.length === 0) {
			return
		}

		const observer = new IntersectionObserver(
			(entries) => {
				for (const entry of entries) {
					if (entry.isIntersecting) {
						setActiveIndex(Number((entry.target as HTMLElement).dataset.index))
					}
				}
			},
			{ root, threshold: 0.6 }
		)

		root
			.querySelectorAll<HTMLElement>("[data-index]")
			.forEach((slide) => observer.observe(slide))

		return () => observer.disconnect()
	}, [karaokes])

	// Jump to the requested karaoke once the list is in.
	useEffect(() => {
		if (!initialId || hasScrolledToInitial.current || karaokes.length === 0) {
			return
		}

		const index = karaokes.findIndex((karaoke) => karaoke.id === initialId)

		if (index > 0) {
			scroller.current
				?.querySelector<HTMLElement>(`[data-index="${index}"]`)
				?.scrollIntoView({ block: "start" })
			setActiveIndex(index)
		}

		hasScrolledToInitial.current = true
	}, [initialId, karaokes])

	const { isPlaying, toggle } = player

	const onUnmute = useCallback(() => {
		setMuted(false)

		// One soundtrack at a time: hush the app-wide player.
		if (isPlaying) {
			toggle()
		}
	}, [isPlaying, toggle])

	const onToggleMute = useCallback(() => {
		if (muted) {
			onUnmute()
		} else {
			setMuted(true)
		}
	}, [muted, onUnmute])

	const onAutoplayBlocked = useCallback(() => setMuted(true), [])

	return (
		<div className="fixed inset-0 z-[35] bg-black md:relative md:inset-auto md:z-auto md:mx-auto md:h-[calc(100dvh-7rem)] md:w-full md:max-w-[28rem] md:overflow-hidden md:rounded-3xl md:border md:border-border md:shadow-xl">
			<div
				ref={scroller}
				tabIndex={0}
				aria-label="Karaoke feed"
				className="size-full snap-y snap-mandatory overflow-y-auto overscroll-contain outline-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
				{isPending && (
					<div className="relative size-full">
						<Skeleton className="size-full rounded-none bg-white/10" />
						<div className="absolute inset-x-4 bottom-32 space-y-3 md:bottom-6">
							<div className="flex items-center gap-2">
								<Skeleton className="size-9 rounded-full bg-white/20" />
								<Skeleton className="h-4 w-32 bg-white/20" />
							</div>
							<Skeleton className="h-4 w-3/4 bg-white/20" />
							<Skeleton className="h-4 w-1/2 bg-white/20" />
						</div>
					</div>
				)}

				{!isPending && karaokes.length === 0 && (
					<div className="flex size-full items-center justify-center px-8 text-center text-sm text-white/70">
						{empty ?? "No karaokes yet."}
					</div>
				)}

				{karaokes.map((karaoke, index) => (
					<div
						key={karaoke.id}
						data-index={index}
						className="h-full snap-start snap-always">
						<KaraokeSlide
							karaoke={karaoke}
							isActive={index === activeIndex}
							isNear={Math.abs(index - activeIndex) <= 1}
							muted={muted}
							onUnmute={onUnmute}
							onAutoplayBlocked={onAutoplayBlocked}
							onToggleMute={onToggleMute}
						/>
					</div>
				))}
			</div>

			{topBar && (
				<div className="pointer-events-none absolute inset-x-0 top-0 z-10 flex items-center gap-2 px-3 pt-[max(0.75rem,env(safe-area-inset-top))] [&>*]:pointer-events-auto">
					{topBar}
				</div>
			)}
		</div>
	)
}
