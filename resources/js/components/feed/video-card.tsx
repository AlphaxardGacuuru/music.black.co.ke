import { useNavigate } from "@tanstack/react-router"
import { ShoppingCart } from "lucide-react"
import { useState } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Button } from "@/components/ui/button"
import { FeedImage } from "@/components/feed/feed-image"
import { Link } from "@/components/ui/link"
import toast from "@/lib/toast"
import { apiErrorMessage, feedActions } from "@/queries/feed"
import type { Video } from "@/types/music"

/** A video suggestion with add-to-cart and buy shortcuts. */
export function VideoCard({ video }: { video: Video }) {
	const navigate = useNavigate()
	const [inCart, setInCart] = useState(video.inCart)

	const toggleCart = () => {
		setInCart(!inCart)

		return feedActions
			.cartVideo(video.id)
			.then((res) => res.data.message && toast.success(res.data.message))
			.catch((error) => {
				setInCart(inCart)
				toast.error(apiErrorMessage(error))
				throw error
			})
	}

	const onBuy = () => {
		toggleCart()
			.then(() => navigate({ to: "/cart" }))
			.catch(() => undefined)
	}

	return (
		<div className="flex gap-3">
			<Link
				href={`/videos/${video.id}/view`}
				variant="unstyled"
				className="group relative aspect-video w-36 shrink-0 overflow-hidden rounded-xl bg-muted">
				<FeedImage
					src={video.thumbnail}
					className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
				/>
			</Link>
			<div className="min-w-0 flex-1">
				<Link
					href={`/videos/${video.id}/view`}
					variant="unstyled"
					className="line-clamp-2 text-sm font-semibold leading-snug">
					{video.name}
				</Link>
				<div className="mt-1 flex items-center gap-1.5">
					<UserAvatar
						name={video.artistName}
						username={video.username}
						avatar={video.avatar}
						className="size-5 text-[8px]"
					/>
					<p className="truncate text-xs text-muted-foreground">
						{video.username}
						{video.ft && ` ft. ${video.ft}`}
					</p>
				</div>
				{!video.hasBoughtVideo && (
					<div className="mt-2 flex items-center gap-1.5">
						<Button
							variant={inCart ? "default" : "outline"}
							size="icon"
							className="size-7"
							onClick={() => void toggleCart().catch(() => undefined)}
							aria-label={inCart ? "Remove from cart" : "Add to cart"}
							aria-pressed={inCart}>
							<ShoppingCart className="size-3.5" />
						</Button>
						{!inCart && (
							<Button
								size="sm"
								className="h-7 px-3 text-xs"
								onClick={onBuy}>
								KES 20
							</Button>
						)}
					</div>
				)}
			</div>
		</div>
	)
}
