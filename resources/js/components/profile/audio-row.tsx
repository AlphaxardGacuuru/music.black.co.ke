import { Pause, Play } from "lucide-react"
import { FeedImage } from "@/components/feed/feed-image"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { mediaUrl } from "@/lib/media"
import { cn } from "@/lib/utils"
import type { Audio } from "@/types/music"

type Props = {
	audio: Audio
	/** The list it sits in, so next/previous in the player follow it. */
	queue: Audio[]
	onPlay?: () => void
}

/** An audio with a play button wired to the app-wide player. */
export function AudioRow({ audio, queue, onPlay }: Props) {
	const player = useAudioPlayer()
	const isCurrent = player.current?.id === audio.id
	const isPlaying = isCurrent && player.isPlaying

	const onClick = () => {
		onPlay?.()

		if (isCurrent) {
			player.toggle()
		} else {
			player.play(audio, queue)
		}
	}

	return (
		<div className="flex items-center gap-3">
			<button
				type="button"
				onClick={onClick}
				aria-label={isPlaying ? `Pause ${audio.name}` : `Play ${audio.name}`}
				className="group relative size-14 shrink-0 overflow-hidden rounded-xl bg-muted">
				<FeedImage
					src={mediaUrl(audio.thumbnail)}
					className="size-full object-cover"
				/>
				<span
					className={cn(
						"absolute inset-0 flex items-center justify-center bg-black/40 text-white transition-opacity",
						isCurrent ? "opacity-100" : "opacity-0 group-hover:opacity-100"
					)}>
					{isPlaying ? (
						<Pause className="size-5 fill-current" />
					) : (
						<Play className="size-5 fill-current" />
					)}
				</span>
			</button>
			<div className="min-w-0 flex-1">
				<Link
					href={`/audio/show/${audio.id}`}
					variant="unstyled"
					className={cn(
						"line-clamp-1 text-sm font-semibold",
						isCurrent && "text-primary"
					)}>
					{audio.name}
				</Link>
				<p className="truncate text-xs text-muted-foreground">
					{audio.username}
					{audio.ft && ` ft. ${audio.ft}`}
					{audio.album && audio.album !== "Singles" && ` · ${audio.album}`}
				</p>
			</div>
			<Button
				variant="ghost"
				size="icon"
				onClick={onClick}
				aria-label={isPlaying ? "Pause" : "Play"}
				className="shrink-0">
				{isPlaying ? <Pause className="size-4" /> : <Play className="size-4" />}
			</Button>
		</div>
	)
}
