import { Film, ShoppingCart } from "lucide-react"
import { FeedImage } from "@/components/feed/feed-image"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import { useVideoCart } from "@/components/video/use-video-cart"
import { mediaUrl } from "@/lib/media"
import { cn } from "@/lib/utils"
import { VIDEO_PRICE } from "@/types/video"

export type TileVideo = {
	id: string
	name: string
	artistName: string
	username: string
	avatar: string
	ft: string | null
	thumbnail: string | null
	inCart?: boolean
	hasBoughtVideo: boolean
}

type Props = {
	video: TileVideo
	/** "grid" for chart cards, "row" for the compact up-next list. */
	layout?: "grid" | "row"
	className?: string
}

function Thumbnail({ video }: { video: TileVideo }) {
	return (
		<>
			<Film className="absolute inset-0 m-auto size-8 text-muted-foreground/40" />
			<FeedImage
				src={mediaUrl(video.thumbnail)}
				className="relative size-full object-cover transition-transform duration-300 group-hover:scale-105"
			/>
		</>
	)
}

function CartButtons({
	video,
	compact,
}: {
	video: TileVideo
	compact?: boolean
}) {
	const { inCart, onToggle, onBuy } = useVideoCart(
		video.id,
		Boolean(video.inCart)
	)

	if (video.hasBoughtVideo) {
		return null
	}

	return (
		<div className="flex justify-between items-center">
			<Button
				variant={inCart ? "default" : "outline"}
				size="icon"
				className={compact ? "size-7" : "size-8"}
				onClick={onToggle}
				aria-label={inCart ? "Remove from cart" : "Add to cart"}
				aria-pressed={inCart}>
				<ShoppingCart className="size-3.5" />
			</Button>
			{!inCart && (
				<Button
					size="sm"
					className={cn("px-3 text-xs", compact ? "h-7" : "h-8")}
					onClick={onBuy}>
					KES {VIDEO_PRICE}
				</Button>
			)}
		</div>
	)
}

/** A video with its artist and add-to-cart and buy shortcuts. */
export function VideoTile({ video, layout = "grid", className }: Props) {
	const href = `/videos/${video.id}/view`

	if (layout === "row") {
		return (
			<div className={cn("flex gap-3", className)}>
				<Link
					href={href}
					variant="unstyled"
					className="group relative aspect-video w-36 shrink-0 overflow-hidden rounded-xl bg-muted">
					<Thumbnail video={video} />
				</Link>
				<div className="min-w-0 flex-1">
					<Link
						href={href}
						variant="unstyled"
						className="line-clamp-2 text-sm font-semibold leading-snug">
						{video.name}
					</Link>
					<p className="mt-0.5 truncate text-xs text-muted-foreground">
						{video.username}
						{video.ft && ` ft. ${video.ft}`}
					</p>
					<div className="mt-2">
						<CartButtons
							video={video}
							compact
						/>
					</div>
				</div>
			</div>
		)
	}

	return (
		<div className={cn("min-w-0 rounded-2xl bg-secondary pb-2", className)}>
			<Link
				href={href}
				variant="unstyled"
				className="group relative block aspect-video overflow-hidden rounded-2xl border border-border/60 bg-muted">
				<Thumbnail video={video} />
			</Link>
			<div className="flex gap-2.5 m-2">
				<UserAvatar
					name={video.artistName}
					username={video.username}
					avatar={video.avatar}
					className="size-8 text-[10px]"
				/>
				<div className="min-w-0 flex-1">
					<Link
						href={href}
						variant="unstyled"
						className="line-clamp-2 text-sm font-semibold leading-snug">
						{video.name}
					</Link>
					<p className="truncate text-xs text-muted-foreground">
						{video.username}
						{video.ft && ` ft. ${video.ft}`}
					</p>
				</div>
			</div>
			<div className="mx-2">
				<CartButtons
					video={video}
					compact
				/>
			</div>
		</div>
	)
}
