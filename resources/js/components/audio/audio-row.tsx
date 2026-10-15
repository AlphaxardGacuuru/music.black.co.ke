import { useNavigate } from "@tanstack/react-router"
import { Music, Pause, Play, ShoppingCart } from "lucide-react"
import { useState } from "react"
import { FeedImage } from "@/components/feed/feed-image"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { mediaUrl } from "@/lib/media"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { AUDIO_PRICE, audioActions } from "@/queries/audio"
import { apiErrorMessage } from "@/queries/feed"
import type { Audio } from "@/types/music"

type Props = {
	audio: Audio
	/** What next/previous move through when this song is played from here. */
	queue?: Audio[]
	/** Chart position, shown before the artwork. */
	rank?: number
	className?: string
}

/**
 * A song in a list (music.black's AudioMedia): tap the artwork to play it in
 * the app-wide player, the title for its page, plus cart and buy shortcuts.
 */
export function AudioRow({ audio, queue, rank, className }: Props) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const player = useAudioPlayer()
	const [inCart, setInCart] = useState(audio.inCart)

	const isCurrent = player.current?.id === audio.id
	const isPlaying = isCurrent && player.isPlaying
	const showBuy = !audio.hasBoughtAudio && audio.username !== auth?.username

	const onPlay = () => {
		if (isCurrent) {
			player.toggle()
		} else {
			player.play(audio, queue)
		}
	}

	const toggleCart = () => {
		if (!auth) {
			toast.info("Sign in to buy songs.")
			void navigate({ to: "/login" })

			return Promise.reject(new Error("guest"))
		}

		setInCart(!inCart)

		return audioActions
			.cart(audio.id)
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
			})
			.catch((error: unknown) => {
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
		<div className={cn("flex items-center gap-3 py-2", className)}>
			{rank !== undefined && (
				<span className="w-5 shrink-0 text-center text-sm font-semibold tabular-nums text-muted-foreground">
					{rank}
				</span>
			)}
			<button
				type="button"
				onClick={onPlay}
				aria-label={isPlaying ? `Pause ${audio.name}` : `Play ${audio.name}`}
				className="group relative flex size-12 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl bg-muted">
				<Music className="size-5 text-muted-foreground" />
				<FeedImage
					src={mediaUrl(audio.thumbnail)}
					className="absolute inset-0 size-full object-cover"
				/>
				<span
					className={cn(
						"absolute inset-0 flex items-center justify-center bg-black/45 text-white transition-opacity",
						isCurrent ? "opacity-100" : "opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100"
					)}>
					{isPlaying ? (
						<Pause className="size-5 fill-current" />
					) : (
						<Play className="size-5 fill-current" />
					)}
				</span>
			</button>
			<Link
				href={`/audios/${audio.id}/view`}
				variant="unstyled"
				className="min-w-0 flex-1 self-start">
				<span
					className={cn(
						"block truncate text-sm font-semibold",
						isCurrent && "text-primary"
					)}>
					{audio.name}
				</span>
				<span className="block truncate text-xs text-muted-foreground">
					{audio.username}
					{audio.ft && ` ft. ${audio.ft}`}
				</span>
			</Link>
			{showBuy && (
				<div className="flex shrink-0 items-center gap-1.5">
					<Button
						variant={inCart ? "default" : "outline"}
						size="icon"
						className="size-8"
						onClick={() => void toggleCart().catch(() => undefined)}
						aria-label={inCart ? "Remove from cart" : "Add to cart"}
						aria-pressed={inCart}>
						<ShoppingCart className="size-3.5" />
					</Button>
					{!inCart && (
						<Button
							size="sm"
							className="h-8 px-3 text-xs"
							onClick={onBuy}>
							KES {AUDIO_PRICE}
						</Button>
					)}
				</div>
			)}
		</div>
	)
}

/** A loading placeholder shaped like an AudioRow. */
export function AudioRowSkeleton() {
	return (
		<div className="flex items-center gap-3 py-2">
			<Skeleton className="size-12 shrink-0 rounded-xl" />
			<div className="flex-1 space-y-2">
				<Skeleton className="h-3.5 w-2/3" />
				<Skeleton className="h-3 w-1/3" />
			</div>
		</div>
	)
}
