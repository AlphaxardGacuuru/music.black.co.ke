import { useLocation } from "@tanstack/react-router"
import { Loader2, Music, Pause, Play, SkipForward, X } from "lucide-react"
import { FeedImage } from "@/components/feed/feed-image"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { mediaUrl } from "@/lib/media"
import { cn } from "@/lib/utils"

/** Whether the mini player shows on this page (the song page has the full player). */
export function useShowsPlayerBar(): boolean {
	const { current } = useAudioPlayer()
	const { pathname } = useLocation()

	return Boolean(current) && !/^\/audios\/[^/]+\/view$/.test(pathname)
}

/**
 * The persistent mini player (music.black's bottom player): sits above the
 * mobile bottom nav and at the bottom of the content on desktop.
 */
export function AudioPlayerBar({ className }: { className?: string }) {
	const {
		current,
		isPlaying,
		isLoading,
		toggle,
		next,
		queue,
		currentTime,
		duration,
		close,
	} = useAudioPlayer()
	const visible = useShowsPlayerBar()

	if (!visible || !current) {
		return null
	}

	const percent = duration > 0 ? (currentTime / duration) * 100 : 0

	return (
		<div
			className={cn(
				"pointer-events-none sticky bottom-24 z-30 px-3 pb-2 md:bottom-0 md:px-4 md:pb-4",
				className
			)}>
			<div className="pointer-events-auto mx-auto max-w-3xl overflow-hidden rounded-2xl border border-border/60 bg-background/85 shadow-lg backdrop-blur-xl supports-backdrop-filter:bg-background/70">
				<div className="flex items-center gap-3 p-2 pe-3">
					<Link
						href={`/audios/${current.id}/view`}
						variant="unstyled"
						className="flex min-w-0 flex-1 items-center gap-3">
						<span className="relative flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-muted">
							<Music className="size-5 text-muted-foreground" />
							<FeedImage
								src={mediaUrl(current.thumbnail)}
								className="absolute inset-0 size-full object-cover"
							/>
						</span>
						<span className="min-w-0">
							<span className="block truncate text-sm font-semibold">
								{current.name}
							</span>
							<span className="block truncate text-xs text-muted-foreground">
								{current.username}
								{current.ft && ` ft. ${current.ft}`}
							</span>
						</span>
					</Link>
					<Button
						size="icon"
						className="size-10 shrink-0"
						onClick={toggle}
						aria-label={isPlaying ? "Pause" : "Play"}>
						{isLoading && isPlaying ? (
							<Loader2 className="size-5 animate-spin" />
						) : isPlaying ? (
							<Pause className="size-5 fill-current" />
						) : (
							<Play className="size-5 translate-x-px fill-current" />
						)}
					</Button>
					<Button
						size="icon"
						variant="ghost"
						className="size-9 shrink-0"
						onClick={next}
						disabled={queue.length < 2}
						aria-label="Next song">
						<SkipForward className="size-5 fill-current" />
					</Button>
					<Button
						size="icon"
						variant="ghost"
						className="size-9 shrink-0 text-muted-foreground"
						onClick={close}
						aria-label="Close player">
						<X className="size-5" />
					</Button>
				</div>
				<div
					className="h-0.5 bg-muted"
					role="progressbar"
					aria-valuemin={0}
					aria-valuemax={100}
					aria-valuenow={Math.round(percent)}>
					<div
						className="h-full bg-primary transition-[width] duration-200"
						style={{ width: `${percent}%` }}
					/>
				</div>
			</div>
		</div>
	)
}
