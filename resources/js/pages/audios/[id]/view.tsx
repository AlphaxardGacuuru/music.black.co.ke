import { useQueryClient } from "@tanstack/react-query"
import { useNavigate } from "@tanstack/react-router"
import {
	Award,
	Check,
	ChevronDown,
	Download,
	Heart,
	Music,
	Share2,
	ShoppingCart,
} from "lucide-react"
import { useEffect, useMemo, useRef, useState } from "react"
import { AudioControls } from "@/components/audio/audio-controls"
import { AudioRow, AudioRowSkeleton } from "@/components/audio/audio-row"
import { FeedImage } from "@/components/feed/feed-image"
import { UserAvatar } from "@/components/feed/user-avatar"
import { CommentSection } from "@/components/music/comment-section"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { mediaUrl } from "@/lib/media"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import {
	AUDIO_PRICE,
	audioActions,
	audioKeys,
	boughtToAudio,
	useAudio,
	useAudios,
	useBoughtAudios,
} from "@/queries/audio"
import { apiErrorMessage } from "@/queries/feed"
import type { Audio } from "@/types/music"

export const isPublic = true

const PLACEHOLDERS = [1, 2, 3, 4]

/**
 * A song's page (music.black's /audio/show/:id): the full player, like,
 * share, buy or download, the artist, comments, and up next.
 */
export default function AudioShow({ id }: { id: string }) {
	const { auth } = useApp()
	const query = useAudio(id)
	const audios = useAudios()
	const bought = useBoughtAudios(Boolean(auth))
	const player = useAudioPlayer()
	const [tab, setTab] = useState<"comments" | "next">("comments")

	const audio = query.data

	const boughtQueue = useMemo(
		() => (bought.data ?? []).map(boughtToAudio),
		[bought.data]
	)

	// Load this song into the player when the page opens (as music.black
	// did), queued with the listener's bought songs.
	const startedFor = useRef<string | null>(null)
	useEffect(() => {
		if (!audio || (auth && !bought.isFetched)) {
			return
		}

		if (player.current?.id === audio.id) {
			player.update(audio)
			startedFor.current = audio.id

			return
		}

		if (startedFor.current === audio.id) {
			return
		}

		startedFor.current = audio.id

		const inQueue = player.queue.some((item) => item.id === audio.id)
		player.play(
			audio,
			inQueue
				? undefined
				: [audio, ...boughtQueue.filter((item) => item.id !== audio.id)]
		)
	}, [audio, bought.isFetched])

	const upNext = player.queue.filter((item) => item.id !== id)

	const suggestions = (audios.data ?? [])
		.filter(
			(item) =>
				!item.hasBoughtAudio &&
				item.username !== auth?.username &&
				item.id !== id
		)
		.slice(0, 10)

	if (query.isError) {
		return (
			<GlassCard className="mx-auto mt-6 w-full max-w-md p-8 text-center">
				<Head title="Song not found | Black Music" />
				<p className="font-semibold">This song isn't available.</p>
				<Link
					href="/audios/charts"
					variant="outline"
					className="mt-4">
					Browse audio charts
				</Link>
			</GlassCard>
		)
	}

	return (
		<div className="mx-auto grid w-full max-w-6xl gap-6 py-2 md:py-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
			<Head title={`${audio?.name ?? "Audio"} | Black Music`} />

			<main className="min-w-0 space-y-5">
				{audio ? (
					<SongHeader
						key={audio.id}
						audio={audio}
						queue={
							player.queue.some((item) => item.id === audio.id)
								? undefined
								: [audio, ...boughtQueue.filter((item) => item.id !== audio.id)]
						}
					/>
				) : (
					<GlassCard className="space-y-5 p-4 md:p-6">
						<Skeleton className="mx-auto aspect-square w-full max-w-sm rounded-2xl" />
						<Skeleton className="h-6 w-2/3" />
						<Skeleton className="h-4 w-1/3" />
						<Skeleton className="h-16 w-full" />
					</GlassCard>
				)}

				<div className="flex gap-1 rounded-full border border-border/60 p-1 lg:hidden">
					{(
						[
							["comments", "Comments"],
							["next", "Up next"],
						] as const
					).map(([value, label]) => (
						<button
							key={value}
							type="button"
							onClick={() => setTab(value)}
							aria-pressed={tab === value}
							className={cn(
								"flex-1 cursor-pointer rounded-full py-2 text-sm font-medium transition-colors",
								tab === value
									? "bg-primary text-primary-foreground"
									: "text-muted-foreground"
							)}>
							{label}
						</button>
					))}
				</div>

				<section className={cn(tab !== "comments" && "hidden lg:block")}>
					{audio && <Comments audio={audio} />}
				</section>
			</main>

			<aside className={cn("space-y-5", tab !== "next" && "hidden lg:block")}>
				<GlassCard className="p-4">
					<h2 className="mb-1 font-semibold">Up next</h2>
					{auth && bought.isPending && <AudioRowSkeleton />}
					{upNext.map((item) => (
						<AudioRow
							key={item.id}
							audio={item}
						/>
					))}
					{upNext.length === 0 && !(auth && bought.isPending) && (
						<p className="py-2 text-sm text-muted-foreground">
							{auth
								? "You haven't bought any other songs yet."
								: "Sign in and buy songs to build your queue."}
						</p>
					)}
				</GlassCard>

				<GlassCard className="p-4">
					<h2 className="mb-1 font-semibold">Songs to listen to</h2>
					{audios.isPending &&
						PLACEHOLDERS.map((key) => <AudioRowSkeleton key={key} />)}
					{suggestions.map((item) => (
						<AudioRow
							key={item.id}
							audio={item}
						/>
					))}
					{audios.isSuccess && suggestions.length === 0 && (
						<p className="py-2 text-sm text-muted-foreground">
							No more songs to suggest.
						</p>
					)}
				</GlassCard>
			</aside>
		</div>
	)
}

function SongHeader({ audio, queue }: { audio: Audio; queue?: Audio[] }) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()

	const [hasLiked, setHasLiked] = useState(audio.hasLiked)
	const [likes, setLikes] = useState(audio.likes)
	const [inCart, setInCart] = useState(audio.inCart)
	const [hasFollowed, setHasFollowed] = useState(audio.hasFollowed)
	const [showMore, setShowMore] = useState(false)
	const [downloading, setDownloading] = useState(false)

	const isOwner = audio.username === auth?.username
	const canDownload = audio.hasBoughtAudio || isOwner

	const requireAuth = (action: string) => {
		if (auth) {
			return true
		}

		toast.info(`Sign in to ${action}.`)
		void navigate({ to: "/login" })

		return false
	}

	const onLike = () => {
		if (!requireAuth("like songs")) {
			return
		}

		setHasLiked(!hasLiked)
		setLikes(likes + (hasLiked ? -1 : 1))

		audioActions
			.like(audio.id)
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
				void queryClient.invalidateQueries({ queryKey: audioKeys.show(audio.id) })
			})
			.catch((error: unknown) => {
				setHasLiked(hasLiked)
				setLikes(likes)
				toast.error(apiErrorMessage(error))
			})
	}

	const toggleCart = () => {
		if (!requireAuth("buy songs")) {
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

	const onDownload = () => {
		setDownloading(true)
		toast.info(`Downloading ${audio.name}`)
		audioActions
			.download(audio)
			.catch((error: unknown) => toast.error(apiErrorMessage(error)))
			.finally(() => setDownloading(false))
	}

	const onShare = async () => {
		const url = `${window.location.origin}/audios/${audio.id}/view`
		const shareData = {
			title: audio.name,
			text: `Check out ${audio.name} on Black Music`,
			url,
		}

		if (navigator.share && (!navigator.canShare || navigator.canShare(shareData))) {
			try {
				await navigator.share(shareData)
			} catch {
				// Share sheet dismissed.
			}

			return
		}

		try {
			await navigator.clipboard.writeText(url)
			toast.success("Link copied")
		} catch {
			toast.error("Couldn't copy the link.")
		}
	}

	const onFollow = () => {
		if (!requireAuth("follow musicians")) {
			return
		}

		if (!audio.hasBought1 && auth?.username !== "@blackmusic") {
			toast.error(`You must have bought at least one song by ${audio.username}`)

			return
		}

		setHasFollowed(!hasFollowed)

		audioActions
			.follow(audio.username)
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
			})
			.catch((error: unknown) => {
				setHasFollowed(hasFollowed)
				toast.error(apiErrorMessage(error))
			})
	}

	return (
		<GlassCard className="space-y-5 p-4 md:p-6">
			<div className="relative mx-auto flex aspect-square w-full max-w-sm items-center justify-center overflow-hidden rounded-2xl bg-muted shadow-xl">
				<Music className="size-16 text-muted-foreground/50" />
				<FeedImage
					src={mediaUrl(audio.thumbnail)}
					alt={audio.name}
					className="absolute inset-0 size-full object-cover"
				/>
			</div>

			<div className="min-w-0 text-center">
				<h1 className="truncate text-xl font-bold md:text-2xl">{audio.name}</h1>
				<p className="truncate text-sm text-muted-foreground">
					{audio.username}
					{audio.ft && ` ft. ${audio.ft}`}
				</p>
			</div>

			<AudioControls
				audio={audio}
				queue={queue}
			/>

			<div className="flex flex-wrap items-center justify-between gap-2 border-t border-border/60 pt-4">
				<div className="flex items-center gap-1">
					<Button
						variant="ghost"
						size="sm"
						onClick={onLike}
						aria-pressed={hasLiked}
						className={cn("gap-1.5", hasLiked && "text-red-500 hover:text-red-500")}>
						<Heart className={cn("size-5", hasLiked && "fill-current")} />
						<span className="tabular-nums">{likes}</span>
					</Button>
					<Button
						variant="ghost"
						size="sm"
						className="gap-1.5"
						onClick={() => void onShare()}>
						<Share2 className="size-4" />
						Share
					</Button>
				</div>

				{canDownload ? (
					<Button
						variant="outline"
						size="sm"
						className="gap-1.5"
						disabled={downloading}
						onClick={onDownload}>
						<Download className="size-4" />
						Download
					</Button>
				) : (
					<div className="flex items-center gap-1.5">
						<Button
							variant={inCart ? "default" : "outline"}
							size="icon"
							className="size-8"
							onClick={() => void toggleCart().catch(() => undefined)}
							aria-label={inCart ? "Remove from cart" : "Add to cart"}
							aria-pressed={inCart}>
							<ShoppingCart className="size-4" />
						</Button>
						{!inCart && (
							<Button
								size="sm"
								onClick={onBuy}>
								KES {AUDIO_PRICE}
							</Button>
						)}
					</div>
				)}
			</div>

			<dl className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
				{[
					["Album", audio.album],
					["Genre", audio.genre],
					["Released", audio.released],
					["Downloads", String(audio.downloads)],
				].map(([label, value]) => (
					<div
						key={label}
						className="min-w-0">
						<dt className="text-xs text-muted-foreground">{label}</dt>
						<dd className="truncate font-medium">{value || "-"}</dd>
					</div>
				))}
			</dl>

			{audio.description && (
				<div>
					<button
						type="button"
						onClick={() => setShowMore(!showMore)}
						aria-expanded={showMore}
						className="flex cursor-pointer items-center gap-1 text-sm font-medium text-primary">
						{showMore ? "Show less" : "Read more"}
						<ChevronDown
							className={cn("size-4 transition-transform", showMore && "rotate-180")}
						/>
					</button>
					<p
						className={cn(
							"mt-2 whitespace-pre-line text-sm text-muted-foreground",
							!showMore && "line-clamp-2"
						)}>
						{audio.description}
					</p>
				</div>
			)}

			<div className="flex items-center gap-3 border-t border-border/60 pt-4">
				<UserAvatar
					name={audio.artistName}
					username={audio.username}
					avatar={audio.avatar}
				/>
				<Link
					href={`/profile/${audio.username}/view`}
					variant="unstyled"
					className="min-w-0 flex-1">
					<span className="block truncate font-semibold">{audio.artistName}</span>
					<span className="flex items-center gap-2 text-xs text-muted-foreground">
						<span className="truncate">{audio.username}</span>
						<span className="inline-flex items-center gap-0.5 text-primary">
							<Award className="size-3.5" />
							{audio.artistDecos}
						</span>
					</span>
				</Link>
				{!isOwner && (
					<Button
						size="sm"
						variant={hasFollowed ? "secondary" : "outline"}
						className="gap-1"
						onClick={onFollow}>
						{hasFollowed ? (
							<>
								Followed
								<Check className="size-3.5" />
							</>
						) : (
							"Follow"
						)}
					</Button>
				)}
			</div>
		</GlassCard>
	)
}

/** Comments are for the musician and the song's buyers, as on music.black. */
function Comments({ audio }: { audio: Audio }) {
	const { auth } = useApp()
	const queryClient = useQueryClient()

	const canComment =
		audio.hasBoughtAudio ||
		(auth !== undefined &&
			(audio.username === auth.username || auth.username === "@blackmusic"))

	if (!canComment) {
		return (
			<GlassCard className="p-5 text-center text-sm text-muted-foreground">
				Buy this song to read and join the conversation.
			</GlassCard>
		)
	}

	return (
		<GlassCard className="p-4 md:p-5">
			<h2 className="mb-3 font-semibold">Comments</h2>
			<CommentSection
				kind="audio"
				parentId={audio.id}
				onChange={() =>
					void queryClient.invalidateQueries({ queryKey: audioKeys.show(audio.id) })
				}
			/>
		</GlassCard>
	)
}
