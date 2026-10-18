import { useQueryClient } from "@tanstack/react-query"
import {
	Award,
	Heart,
	MessageCircle,
	MoreVertical,
	Share2,
} from "lucide-react"
import { useState } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { FeedImage } from "@/components/feed/feed-image"
import { Link } from "@/components/ui/link"
import { useApp } from "@/contexts/AppContext"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { apiErrorMessage, feedActions, feedKeys } from "@/queries/feed"
import type { Post } from "@/types/music"

type Props = {
	post: Post
	onDeleted: (postId: string) => void
}

const POLL_SLOTS = [1, 2, 3, 4, 5] as const

export function PostCard({ post, onDeleted }: Props) {
	const { auth } = useApp()
	const queryClient = useQueryClient()

	const [hasLiked, setHasLiked] = useState(post.hasLiked)
	const [likes, setLikes] = useState(post.likes)
	const [votes, setVotes] = useState(() =>
		POLL_SLOTS.map((slot) => post[`hasVoted${slot}`])
	)

	const isOwnPost = post.username === auth?.username
	const canModerate = !isOwnPost && post.username !== "@blackmusic"
	const hasPoll = Boolean(post.parameter1)
	const showVoteTotal = hasPoll && (isOwnPost || !post.isWithin24Hrs)

	const refreshFeed = () => {
		void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
		void queryClient.invalidateQueries({ queryKey: feedKeys.artists })
	}

	const onLike = () => {
		setHasLiked(!hasLiked)
		setLikes(likes + (hasLiked ? -1 : 1))

		feedActions.likePost(post.id).catch((error) => {
			setHasLiked(hasLiked)
			setLikes(likes)
			toast.error(apiErrorMessage(error))
		})
	}

	const onVote = (index: number, parameter: string) => {
		setVotes(votes.map((voted, i) => (i === index ? !voted : voted)))

		feedActions
			.vote(post.id, parameter)
			.then((res) => res.data.message && toast.success(res.data.message))
			.catch((error) => {
				setVotes(votes)
				toast.error(apiErrorMessage(error))
			})
	}

	const onShare = () => {
		const shareData = {
			title: post.text ?? `${post.name} on Black Music`,
			text: `Check out this post by ${post.username} on Black Music`,
			url: `${window.location.origin}/post/${post.id}/view`,
		}

		if (navigator.canShare?.(shareData)) {
			void navigator.share(shareData)
		} else {
			void navigator.clipboard.writeText(shareData.url)
			toast.success("Link copied")
		}
	}

	const runAndRefresh = (request: Promise<{ data: { message?: string } }>) => {
		request
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
				refreshFeed()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
	}

	const onDelete = () => {
		onDeleted(post.id)

		feedActions
			.deletePost(post.id)
			.then((res) => res.data.message && toast.success(res.data.message))
			.catch((error) => {
				toast.error(apiErrorMessage(error))
				refreshFeed()
			})
	}

	return (
		<GlassCard className="p-4 sm:p-5">
			<div className="flex items-start gap-3">
				<UserAvatar
					name={post.name}
					username={post.username}
					avatar={post.avatar}
				/>
				<div className="min-w-0 flex-1">
					<div className="flex flex-wrap items-baseline gap-x-2">
						<p className="truncate font-semibold">{post.name}</p>
						<p className="truncate text-sm text-muted-foreground">
							{post.username}
						</p>
						{post.decos > 0 && (
							<span className="inline-flex items-center gap-0.5 text-xs font-medium text-primary">
								<Award className="size-3.5" />
								{post.decos}
							</span>
						)}
					</div>
					<p className="text-xs text-muted-foreground">
						{post.createdAt}
						{post.hasEdited && " · Edited"}
					</p>
				</div>

				<DropdownMenu>
					<DropdownMenuTrigger asChild>
						<Button
							variant="ghost"
							size="icon"
							aria-label="Post options">
							<MoreVertical className="size-5" />
						</Button>
					</DropdownMenuTrigger>
					<DropdownMenuContent align="end">
						{canModerate && (
							<>
								<DropdownMenuItem
									onSelect={() =>
										runAndRefresh(feedActions.mutePosts(post.username))
									}>
									{post.hasMuted ? "Unmute" : "Mute"} {post.username}
								</DropdownMenuItem>
								<DropdownMenuItem
									onSelect={() =>
										runAndRefresh(feedActions.follow(post.username))
									}>
									{post.hasFollowed ? "Unfollow" : "Follow"} {post.username}
								</DropdownMenuItem>
							</>
						)}
						{isOwnPost && (
							<>
								<DropdownMenuItem asChild>
									<Link
										href={`/post/${post.id}/edit`}
										variant="unstyled">
										Edit post
									</Link>
								</DropdownMenuItem>
								<DropdownMenuItem
									variant="destructive"
									onSelect={onDelete}>
									Delete post
								</DropdownMenuItem>
							</>
						)}
					</DropdownMenuContent>
				</DropdownMenu>
			</div>

			{post.text && (
				<Link
					href={`/post/${post.id}/view`}
					variant="unstyled"
					className="mt-3 block whitespace-pre-line text-[15px] leading-relaxed">
					{post.text}
				</Link>
			)}

			{post.media && (
				<Link
					href={`/post/${post.id}/view`}
					variant="unstyled"
					className="mt-3 block overflow-hidden rounded-2xl border border-border/60">
					<FeedImage
						src={post.media}
						className="max-h-128 w-full object-cover"
					/>
				</Link>
			)}

			{hasPoll && (
				<div className="mt-4 space-y-2">
					{POLL_SLOTS.map((slot, index) => {
						const parameter = post[`parameter${slot}`]

						if (!parameter) {
							return null
						}

						if (post.isWithin24Hrs) {
							return (
								<Button
									key={slot}
									variant={votes[index] ? "default" : "outline"}
									className="w-full justify-start normal-case"
									onClick={() => onVote(index, parameter)}>
									{parameter}
								</Button>
							)
						}

						const percentage = post[`percentage${slot}`]
						const isWinner = parameter === post.winner

						return (
							<div
								key={slot}
								className="relative h-9 overflow-hidden rounded-full border border-border/60">
								<div
									className={cn(
										"absolute inset-y-0 left-0 rounded-full",
										isWinner ? "bg-primary/70" : "bg-muted"
									)}
									style={{ width: `${percentage}%` }}
								/>
								<div className="relative flex h-full items-center justify-between px-4 text-sm">
									<span className={cn(isWinner && "font-semibold")}>
										{parameter}
									</span>
									<span className="tabular-nums">{percentage}%</span>
								</div>
							</div>
						)
					})}
					{showVoteTotal && (
						<p className="text-xs text-muted-foreground">
							Total votes: {post.totalVotes}
						</p>
					)}
				</div>
			)}

			<div className="mt-4 flex items-center gap-1 text-sm text-muted-foreground">
				<Button
					variant="ghost"
					size="sm"
					onClick={onLike}
					aria-pressed={hasLiked}
					className={cn("gap-1.5", hasLiked && "text-primary")}>
					<Heart className={cn("size-4", hasLiked && "fill-current")} />
					<span className="tabular-nums">{likes}</span>
				</Button>
				<Link
					href={`/post/${post.id}/view`}
					variant="ghost"
					size="sm"
					className="gap-1.5">
					<MessageCircle className="size-4" />
					<span className="tabular-nums">{post.comments}</span>
				</Link>
				<Button
					variant="ghost"
					size="sm"
					onClick={onShare}
					aria-label="Share post"
					className="ml-auto">
					<Share2 className="size-4" />
				</Button>
			</div>
		</GlassCard>
	)
}
