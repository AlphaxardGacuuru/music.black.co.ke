import { Pause, Play } from "lucide-react"
import type { ReactNode } from "react"
import { FeedImage } from "@/components/feed/feed-image"
import { Link } from "@/components/ui/link"
import { mediaUrl } from "@/lib/media"
import { cn } from "@/lib/utils"

type Props = {
	kind: "video" | "audio"
	id: string
	name: string
	username: string
	ft: string | null
	thumbnail: string
	/** Audios only: start this song in the app-wide player. */
	onPlay?: () => void
	isPlaying?: boolean
	/** Buttons on the right (price, remove, download). */
	children?: ReactNode
	className?: string
}

/**
 * A compact video or audio line used by the cart, receipts and the library:
 * thumbnail (16:9 for videos, square with a play button for audios), title,
 * artist and whatever actions the page passes in.
 */
export function SongRow({
	kind,
	id,
	name,
	username,
	ft,
	thumbnail,
	onPlay,
	isPlaying = false,
	children,
	className,
}: Props) {
	const href = `/${kind}s/${id}/view`

	return (
		<div className={cn("flex items-center gap-3", className)}>
			{kind === "audio" && onPlay ? (
				<button
					type="button"
					onClick={onPlay}
					aria-label={isPlaying ? `Pause ${name}` : `Play ${name}`}
					className="group relative size-14 shrink-0 overflow-hidden rounded-xl bg-muted">
					<FeedImage
						src={mediaUrl(thumbnail)}
						className="size-full object-cover"
					/>
					<span
						className={cn(
							"absolute inset-0 flex items-center justify-center bg-black/40 text-white transition-opacity",
							isPlaying ? "opacity-100" : "opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100"
						)}>
						{isPlaying ? (
							<Pause className="size-5" />
						) : (
							<Play className="size-5" />
						)}
					</span>
				</button>
			) : (
				<Link
					href={href}
					variant="unstyled"
					className={cn(
						"group relative shrink-0 overflow-hidden rounded-xl bg-muted",
						kind === "video" ? "aspect-video w-28 sm:w-32" : "size-14"
					)}>
					<FeedImage
						src={mediaUrl(thumbnail)}
						className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
					/>
				</Link>
			)}

			<div className="min-w-0 flex-1">
				<Link
					href={href}
					variant="unstyled"
					className={cn(
						"line-clamp-2 justify-start text-sm font-semibold leading-snug normal-case",
						isPlaying && "text-primary"
					)}>
					{name}
				</Link>
				<p className="truncate text-xs text-muted-foreground">
					{username}
					{ft && ` ft. ${ft}`}
				</p>
			</div>

			{children && (
				<div className="flex shrink-0 items-center gap-1.5">{children}</div>
			)}
		</div>
	)
}
