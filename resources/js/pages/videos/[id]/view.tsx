import { useQueryClient } from "@tanstack/react-query"
import {
	Award,
	Check,
	ChevronDown,
	Download,
	Heart,
	Pencil,
	Share2,
	ShoppingCart,
} from "lucide-react"
import { useState } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { CommentSection } from "@/components/music/comment-section"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import {
	useRequireAccount,
	useVideoCart,
} from "@/components/video/use-video-cart"
import { VideoPlayer } from "@/components/video/video-player"
import { VideoTile, type TileVideo } from "@/components/video/video-tile"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { apiErrorMessage } from "@/queries/feed"
import {
	useAllVideos,
	useArtistVideos,
	useBoughtVideos,
	useVideo,
	videoActions,
	videoKeys,
	videoSource,
} from "@/queries/video"
import type { VideoDetail } from "@/types/video"
import { VIDEO_PRICE } from "@/types/video"
import { ADMIN_EMAIL } from "@/middleware/auth"

export const isPublic = true

const PLACEHOLDERS = [1, 2, 3, 4]

export default function VideoShow({ id }: { id: string }) {
	const video = useVideo(id)

	return (
		<>
			<Head title={`${video.data?.name ?? "Video"} | Black Music`} />
			{video.isPending ? (
				<LoadingVideo />
			) : video.data ? (
				<VideoPage
					key={video.data.id}
					video={video.data}
				/>
			) : (
				<p className="px-4 py-16 text-center text-sm text-muted-foreground">
					This video could not be found.
				</p>
			)}
		</>
	)
}

function LoadingVideo() {
	return (
		<div className="mx-auto grid w-full max-w-7xl gap-6 px-4 py-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-6">
			<div className="space-y-4">
				<Skeleton className="aspect-video w-full rounded-3xl" />
				<Skeleton className="h-6 w-2/3" />
				<Skeleton className="h-4 w-1/3" />
			</div>
			<div className="hidden space-y-4 lg:block">
				{PLACEHOLDERS.map((key) => (
					<Skeleton
						key={key}
						className="h-20 w-full rounded-xl"
					/>
				))}
			</div>
		</div>
	)
}

function VideoPage({ video }: { video: VideoDetail }) {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const requireAccount = useRequireAccount()
	const cart = useVideoCart(video.id, video.inCart)

	const [hasLiked, setHasLiked] = useState(video.hasLiked)
	const [likes, setLikes] = useState(video.likes)
	const [hasFollowed, setHasFollowed] = useState(video.hasFollowed)
	const [showMore, setShowMore] = useState(false)
	const [tab, setTab] = useState<"comments" | "recommended">("comments")

	const isOwner = auth?.username === video.username
	const isAdmin = auth?.username === "@blackmusic" || auth?.email === ADMIN_EMAIL
	const fullAccess = video.hasBoughtVideo || isOwner || isAdmin
	const canDownload = fullAccess && !videoSource(video.video).isLink
	const canFollow = video.hasBought1 || isAdmin

	const refresh = () =>
		void queryClient.invalidateQueries({ queryKey: videoKeys.show(video.id) })

	const onLike = () => {
		if (!requireAccount("Sign in to like videos")) {
			return
		}

		setHasLiked(!hasLiked)
		setLikes(likes + (hasLiked ? -1 : 1))

		videoActions
			.like(video.id)
			.then((res) => res.data.message && toast.success(res.data.message))
			.catch((error) => {
				setHasLiked(hasLiked)
				setLikes(likes)
				toast.error(apiErrorMessage(error))
			})
	}

	const onFollow = () => {
		if (!requireAccount("Sign in to follow musicians")) {
			return
		}

		if (!canFollow) {
			toast.error(
				`You must have bought at least one song by ${video.username}`
			)

			return
		}

		setHasFollowed(!hasFollowed)

		videoActions
			.follow(video.username)
			.then((res) => res.data.message && toast.success(res.data.message))
			.catch((error) => {
				setHasFollowed(hasFollowed)
				toast.error(apiErrorMessage(error))
			})
	}

	const onShare = () => {
		const shareData = {
			title: video.name,
			text: `Check out ${video.name} by ${video.username} on Black Music`,
			url: `${window.location.origin}/video/show/${video.id}`,
		}

		if (navigator.canShare?.(shareData)) {
			void navigator.share(shareData).catch(() => undefined)
		} else {
			void navigator.clipboard
				.writeText(shareData.url)
				.then(() => toast.success("Link copied"))
		}
	}

	const onDownload = () => {
		toast.info(`Downloading ${video.name}`)
		videoActions
			.download(video.id)
			.catch((error: unknown) => toast.error(apiErrorMessage(error)))
	}

	return (
		<div className="mx-auto grid w-full max-w-7xl gap-6 px-4 py-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-6">
			<main className="min-w-0 space-y-5">
				<VideoPlayer
					path={video.video}
					thumbnail={video.thumbnail}
					name={video.name}
					fullAccess={fullAccess}
					onBuy={cart.onBuy}
				/>

				{/* Title and actions */}
				<div className="space-y-3">
					<div>
						<h1 className="text-xl font-bold leading-tight sm:text-2xl">
							{video.name}
						</h1>
						<p className="mt-1 text-sm text-muted-foreground">
							{video.username}
							{video.ft && ` ft. ${video.ft}`}
						</p>
					</div>

					<div className="flex flex-wrap items-center gap-2">
						<Button
							variant={hasLiked ? "default" : "outline"}
							size="sm"
							onClick={onLike}
							aria-pressed={hasLiked}>
							<Heart className={cn("size-4", hasLiked && "fill-current")} />
							<span className="tabular-nums">{likes}</span>
						</Button>
						<Button
							variant="outline"
							size="sm"
							onClick={onShare}>
							<Share2 className="size-4" />
							Share
						</Button>

						{canDownload && (
							<Button
								size="sm"
								onClick={onDownload}>
								<Download className="size-4" />
								Download
							</Button>
						)}

						{!fullAccess && (
							<>
								<Button
									variant={cart.inCart ? "default" : "outline"}
									size="sm"
									onClick={cart.onToggle}
									aria-pressed={cart.inCart}>
									<ShoppingCart className="size-4" />
									{cart.inCart ? "In cart" : "Add to cart"}
								</Button>
								<Button
									size="sm"
									onClick={cart.onBuy}>
									{cart.inCart ? "Checkout" : `Buy KES ${VIDEO_PRICE}`}
								</Button>
							</>
						)}

						{isOwner && (
							<Link
								href={`/video/edit/${video.id}`}
								variant="outline"
								size="sm">
								<Pencil className="size-4" />
								Edit
							</Link>
						)}
					</div>
				</div>

				{/* Details */}
				<GlassCard className="p-4 sm:p-5">
					<div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
						{video.album && <span>{video.album}</span>}
						{video.genre && <span>{video.genre}</span>}
						<span>Released {video.released}</span>
						<span>{video.downloads} downloads</span>
					</div>
					{video.description && (
						<div className="mt-3">
							<p
								className={cn(
									"whitespace-pre-line text-sm leading-relaxed",
									!showMore && "line-clamp-2"
								)}>
								{video.description}
							</p>
							<button
								type="button"
								className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-primary"
								onClick={() => setShowMore(!showMore)}>
								{showMore ? "Show less" : "Read more"}
								<ChevronDown
									className={cn(
										"size-3.5 transition-transform",
										showMore && "rotate-180"
									)}
								/>
							</button>
						</div>
					)}

					{/* Artist */}
					<div className="mt-4 flex items-center gap-3 border-t border-border/60 pt-4">
						<UserAvatar
							name={video.artistName}
							username={video.username}
							avatar={video.avatar}
						/>
						<Link
							href={`/profile/show/${video.username}`}
							variant="unstyled"
							className="min-w-0 flex-1">
							<p className="truncate font-semibold">{video.artistName}</p>
							<p className="flex items-center gap-2 truncate text-xs text-muted-foreground">
								{video.username}
								{video.artistDecos > 0 && (
									<span className="inline-flex items-center gap-0.5 font-medium text-primary">
										<Award className="size-3.5" />
										{video.artistDecos}
									</span>
								)}
							</p>
						</Link>
						{!isOwner && (
							<Button
								size="sm"
								variant={hasFollowed ? "secondary" : "outline"}
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

				{/* Phone tabs between comments and recommendations */}
				<div className="grid grid-cols-2 gap-1 rounded-full border border-border/60 p-1 lg:hidden">
					{(["comments", "recommended"] as const).map((item) => (
						<button
							key={item}
							type="button"
							onClick={() => setTab(item)}
							className={cn(
								"rounded-full py-1.5 text-sm font-medium capitalize transition-colors",
								tab === item
									? "bg-primary text-primary-foreground"
									: "text-muted-foreground"
							)}>
							{item}
						</button>
					))}
				</div>

				<section className={cn(tab !== "comments" && "hidden lg:block")}>
					<h2 className="mb-3 font-semibold">
						Comments{video.comments > 0 && ` (${video.comments})`}
					</h2>
					{fullAccess ? (
						<CommentSection
							kind="video"
							parentId={video.id}
							onChange={refresh}
						/>
					) : (
						<p className="text-sm text-muted-foreground">
							Buy this video to read and join the comments.
						</p>
					)}
				</section>
			</main>

			<aside className={cn(tab !== "recommended" && "hidden lg:block")}>
				<Recommendations video={video} />
			</aside>
		</div>
	)
}

function Recommendations({ video }: { video: VideoDetail }) {
	const { auth } = useApp()
	const artistVideos = useArtistVideos(video.username)
	const bought = useBoughtVideos(Boolean(auth))
	const allVideos = useAllVideos()

	const moreFromArtist = (artistVideos.data ?? []).filter(
		(item) => item.id !== video.id
	)
	const upNext = (bought.data ?? []).filter((item) => item.id !== video.id)
	const toWatch = (allVideos.data ?? [])
		.filter(
			(item) =>
				!item.hasBoughtVideo &&
				item.username !== auth?.username &&
				item.username !== video.username &&
				item.id !== video.id
		)
		.slice(0, 10)

	return (
		<div className="space-y-6">
			{(artistVideos.isPending || moreFromArtist.length > 0) && (
				<VideoList
					title={`More from ${video.username}`}
					loading={artistVideos.isPending}
					videos={moreFromArtist}
				/>
			)}

			{auth && (
				<VideoList
					title="Up next"
					loading={bought.isPending}
					videos={upNext}
					empty="You haven't bought any other videos yet."
				/>
			)}

			<VideoList
				title="Songs to watch"
				loading={allVideos.isPending}
				videos={toWatch}
				empty="No other videos to suggest yet."
			/>
		</div>
	)
}

function VideoList({
	title,
	loading,
	videos,
	empty,
}: {
	title: string
	loading: boolean
	videos: TileVideo[]
	empty?: string
}) {
	return (
		<GlassCard className="p-4">
			<h2 className="mb-3 font-semibold">{title}</h2>
			<div className="space-y-4">
				{loading &&
					PLACEHOLDERS.slice(0, 3).map((key) => (
						<div
							key={key}
							className="flex gap-3">
							<Skeleton className="aspect-video w-36 rounded-xl" />
							<div className="flex-1 space-y-2">
								<Skeleton className="h-4 w-full" />
								<Skeleton className="h-3 w-2/3" />
							</div>
						</div>
					))}
				{videos.map((item) => (
					<VideoTile
						key={item.id}
						video={item}
						layout="row"
					/>
				))}
				{!loading && videos.length === 0 && empty && (
					<p className="text-sm text-muted-foreground">{empty}</p>
				)}
			</div>
		</GlassCard>
	)
}
