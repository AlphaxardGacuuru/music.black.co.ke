import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import {
	Award,
	Bookmark,
	Heart,
	MessageCircle,
	MicVocal,
	Music2,
	Play,
	Share2,
	Volume2,
	VolumeX,
} from "lucide-react"
import { useEffect, useRef, useState, type ReactNode } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { CommentSection } from "@/components/music/comment-section"
import { Link } from "@/components/ui/link"
import {
	Sheet,
	SheetContent,
	SheetDescription,
	SheetHeader,
	SheetTitle,
} from "@/components/ui/sheet"
import { useApp } from "@/contexts/AppContext"
import { mediaUrl } from "@/lib/media"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { apiErrorMessage } from "@/queries/feed"
import { karaokeActions, karaokeKeys } from "@/queries/karaoke"
import type { Karaoke } from "@/types/karaoke"

type Props = {
	karaoke: Karaoke
	/** The slide filling the feed: it plays, every other one is paused. */
	isActive: boolean
	/** Close enough to the active slide to load its video. */
	isNear: boolean
	muted: boolean
	onUnmute: () => void
	/** The browser refused to play with sound, fall back to muted. */
	onAutoplayBlocked: () => void
	onToggleMute: () => void
}

function formatCount(count: number): string {
	if (count >= 1_000_000) {
		return `${(count / 1_000_000).toFixed(1).replace(/\.0$/, "")}M`
	}

	if (count >= 1_000) {
		return `${(count / 1_000).toFixed(1).replace(/\.0$/, "")}K`
	}

	return String(count)
}

/**
 * One full-height karaoke in the feed: the video, who sang it and over which
 * song, and the like, comment, save and share rail.
 */
export function KaraokeSlide({
	karaoke,
	isActive,
	isNear,
	muted,
	onUnmute,
	onAutoplayBlocked,
	onToggleMute,
}: Props) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const video = useRef<HTMLVideoElement>(null)

	const [paused, setPaused] = useState(false)
	const [hasLiked, setHasLiked] = useState(karaoke.hasLiked)
	const [likes, setLikes] = useState(karaoke.likes)
	const [hasSaved, setHasSaved] = useState(karaoke.hasSaved)
	const [showComments, setShowComments] = useState(false)
	const [showMore, setShowMore] = useState(false)

	// Play the slide in view and rewind the ones scrolled away from.
	useEffect(() => {
		const el = video.current

		if (!el) {
			return
		}

		el.muted = muted

		if (!isActive) {
			el.pause()
			el.currentTime = 0

			return
		}

		if (paused) {
			el.pause()

			return
		}

		el.play().catch((error: unknown) => {
			if (error instanceof DOMException && error.name === "NotAllowedError") {
				if (!muted) {
					onAutoplayBlocked()
				} else {
					setPaused(true)
				}
			}
		})
	}, [isActive, isNear, muted, paused, onAutoplayBlocked])

	// Coming back to a slide starts it again.
	useEffect(() => {
		if (!isActive) {
			setPaused(false)
		}
	}, [isActive])

	const requireAuth = (action: string): boolean => {
		if (auth) {
			return true
		}

		toast.error(`Sign in to ${action}.`)
		void navigate({ to: "/login" })

		return false
	}

	const onTap = () => {
		if (muted) {
			onUnmute()
			setPaused(false)

			return
		}

		setPaused(!paused)
	}

	const onLike = () => {
		if (!requireAuth("like karaokes")) {
			return
		}

		setHasLiked(!hasLiked)
		setLikes(likes + (hasLiked ? -1 : 1))

		karaokeActions.like(karaoke.id).catch((error) => {
			setHasLiked(hasLiked)
			setLikes(likes)
			toast.error(apiErrorMessage(error))
		})
	}

	const onSave = () => {
		if (!requireAuth("save karaokes")) {
			return
		}

		setHasSaved(!hasSaved)

		karaokeActions
			.save(karaoke.id)
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
				void queryClient.invalidateQueries({ queryKey: karaokeKeys.saved })
			})
			.catch((error) => {
				setHasSaved(hasSaved)
				toast.error(apiErrorMessage(error))
			})
	}

	const onShare = () => {
		const shareData = {
			title: karaoke.audioName,
			text: `Check out ${karaoke.username} singing ${karaoke.audioName} on Black Music`,
			url: `${window.location.origin}/karaoke/show/${karaoke.id}`,
		}

		if (navigator.canShare?.(shareData)) {
			void navigator.share(shareData).catch(() => undefined)
		} else {
			void navigator.clipboard.writeText(shareData.url)
			toast.success("Link copied")
		}
	}

	const createdAt = karaoke.created_at ?? karaoke.createdAt

	return (
		<article className="relative size-full overflow-hidden bg-black text-white">
			{/* Blurred cover behind the video (and while it loads) */}
			<img
				src={karaoke.audioThumbnail}
				alt=""
				aria-hidden
				className="absolute inset-0 size-full scale-110 object-cover opacity-50 blur-2xl"
			/>

			<video
				ref={video}
				src={isNear ? mediaUrl(karaoke.karaoke) : undefined}
				preload={isNear ? "auto" : "none"}
				className="relative size-full object-cover"
				loop
				playsInline
				muted={muted}
				onClick={onTap}
			/>

			{/* Shade so the white text stays readable on bright videos */}
			<div className="pointer-events-none absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/80 via-black/30 to-transparent" />
			<div className="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-black/50 to-transparent" />

			{paused && isActive && (
				<button
					type="button"
					onClick={onTap}
					aria-label="Play"
					className="absolute inset-0 m-auto flex size-20 items-center justify-center rounded-full bg-black/40 backdrop-blur-sm">
					<Play className="size-9 translate-x-0.5 fill-white" />
				</button>
			)}

			<button
				type="button"
				onClick={onToggleMute}
				aria-label={muted ? "Unmute" : "Mute"}
				className="absolute right-3 top-16 flex size-10 items-center justify-center rounded-full bg-black/40 backdrop-blur-sm md:top-4">
				{muted ? <VolumeX className="size-5" /> : <Volume2 className="size-5" />}
			</button>

			{muted && isActive && (
				<button
					type="button"
					onClick={onTap}
					className="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center gap-2 rounded-full bg-black/50 px-4 py-2 text-sm font-medium backdrop-blur-sm">
					<VolumeX className="size-4" />
					Tap to unmute
				</button>
			)}

			{/* Info and actions */}
			<div className="absolute inset-x-0 bottom-0 flex items-end gap-3 px-4 pb-32 md:pb-5">
				<div className="min-w-0 flex-1 space-y-2">
					<div className="flex items-center gap-2">
						<UserAvatar
							name={karaoke.name}
							username={karaoke.username}
							avatar={karaoke.avatar}
							className="size-9 border border-white/40"
						/>
						<div className="min-w-0">
							<div className="flex items-center gap-1.5">
								<Link
									href={`/profile/show/${karaoke.username}`}
									variant="unstyled"
									className="truncate font-semibold normal-case">
									{karaoke.name}
								</Link>
								{karaoke.decos > 0 && (
									<span className="inline-flex items-center gap-0.5 text-xs font-medium text-primary">
										<Award className="size-3.5" />
										{karaoke.decos}
									</span>
								)}
							</div>
							<p className="truncate text-xs text-white/70">
								{karaoke.username}
								{createdAt && ` · ${createdAt}`}
							</p>
						</div>
					</div>

					{karaoke.description && (
						<button
							type="button"
							onClick={() => setShowMore(!showMore)}
							className={cn(
								"block w-full text-left text-sm leading-snug",
								showMore ? "max-h-40 overflow-y-auto" : "line-clamp-2"
							)}>
							{karaoke.description}
						</button>
					)}

					<div className="flex items-center gap-2">
						<Link
							href={`/audio/show/${karaoke.audioId}`}
							variant="unstyled"
							className="flex min-w-0 items-center gap-1.5 text-sm text-primary normal-case">
							<Music2 className="size-4 shrink-0" />
							<span className="truncate">{karaoke.audioName}</span>
						</Link>
						<Link
							href={`/karaoke/create/${karaoke.audioId}`}
							variant="unstyled"
							className="flex shrink-0 items-center gap-1 rounded-full border border-white/30 bg-white/10 px-2.5 py-1 text-xs font-medium normal-case backdrop-blur-sm">
							<MicVocal className="size-3.5" />
							Sing this
						</Link>
					</div>
				</div>

				<div className="flex shrink-0 flex-col items-center gap-4">
					<RailButton
						label={hasLiked ? "Unlike" : "Like"}
						count={formatCount(likes)}
						active={hasLiked}
						onClick={onLike}>
						<Heart
							className={cn("size-7", hasLiked && "fill-red-500 text-red-500")}
						/>
					</RailButton>
					<RailButton
						label="Comments"
						count={formatCount(karaoke.comments)}
						onClick={() => setShowComments(true)}>
						<MessageCircle className="size-7" />
					</RailButton>
					<RailButton
						label={hasSaved ? "Unsave" : "Save"}
						active={hasSaved}
						onClick={onSave}>
						<Bookmark
							className={cn("size-7", hasSaved && "fill-primary text-primary")}
						/>
					</RailButton>
					<RailButton
						label="Share"
						onClick={onShare}>
						<Share2 className="size-7" />
					</RailButton>
					<Link
						href={`/audio/show/${karaoke.audioId}`}
						variant="unstyled"
						aria-label={`Open ${karaoke.audioName}`}
						className="mt-1 block size-11 overflow-hidden rounded-full border-4 border-neutral-800 animate-spin [animation-duration:6s]"
						style={{ animationPlayState: isActive && !paused ? "running" : "paused" }}>
						<img
							src={karaoke.audioThumbnail}
							alt=""
							className="size-full object-cover"
						/>
					</Link>
				</div>
			</div>

			<Sheet
				open={showComments}
				onOpenChange={setShowComments}>
				<SheetContent
					side="bottom"
					className="mx-auto max-h-[75dvh] max-w-xl rounded-t-3xl">
					<SheetHeader className="pb-0">
						<SheetTitle>{formatCount(karaoke.comments)} comments</SheetTitle>
						<SheetDescription className="truncate">
							{karaoke.username} singing {karaoke.audioName}
						</SheetDescription>
					</SheetHeader>
					<div className="overflow-y-auto px-4 pb-6">
						<CommentSection
							kind="karaoke"
							parentId={karaoke.id}
							className="[&>h2]:hidden"
							onChange={() =>
								void queryClient.invalidateQueries({
									queryKey: karaokeKeys.all,
								})
							}
						/>
					</div>
				</SheetContent>
			</Sheet>
		</article>
	)
}

function RailButton({
	label,
	count,
	active,
	onClick,
	children,
}: {
	label: string
	count?: string
	active?: boolean
	onClick: () => void
	children: ReactNode
}) {
	return (
		<button
			type="button"
			onClick={onClick}
			aria-label={label}
			aria-pressed={active}
			className="flex flex-col items-center gap-1 text-xs font-semibold drop-shadow transition-transform active:scale-90">
			{children}
			{count !== undefined && <span>{count}</span>}
		</button>
	)
}
