import { useQueryClient } from "@tanstack/react-query"
import { Award, Heart, SendHorizontal, Trash2 } from "lucide-react"
import { useState, type FormEvent } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { commentActions, commentKeys, useComments } from "@/queries/comments"
import { apiErrorMessage } from "@/queries/feed"
import type { CommentKind, MusicComment } from "@/types/music"

type Props = {
	kind: CommentKind
	parentId: string
	/** Called after a comment is added or deleted, e.g. to refresh a count. */
	onChange?: () => void
	className?: string
}

/**
 * The comments under a video, audio, karaoke or post: a box to add one (for
 * signed-in users), then the list newest first with likes and, on your own
 * comments, delete.
 */
export function CommentSection({ kind, parentId, onChange, className }: Props) {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const comments = useComments(kind, parentId)
	const [text, setText] = useState("")
	const [sending, setSending] = useState(false)

	const refresh = () => {
		void queryClient.invalidateQueries({
			queryKey: commentKeys.list(kind, parentId),
		})
		onChange?.()
	}

	const onSubmit = (event: FormEvent) => {
		event.preventDefault()

		if (!text.trim()) {
			return
		}

		setSending(true)

		commentActions
			.add(kind, parentId, text.trim())
			.then(() => {
				setText("")
				refresh()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setSending(false))
	}

	const allComments = comments.data?.pages.flatMap((page) => page.data) ?? []

	return (
		<section className={cn("space-y-4", className)}>
			<h2 className="font-semibold">Comments</h2>

			{auth ? (
				<form
					onSubmit={onSubmit}
					className="flex items-end gap-2">
					<textarea
						value={text}
						onChange={(event) => setText(event.target.value)}
						placeholder="Add a comment"
						rows={1}
						maxLength={255}
						className="min-h-10 flex-1 resize-none rounded-2xl border border-input bg-background/70 px-4 py-2.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
					/>
					<Button
						type="submit"
						size="icon"
						disabled={sending || !text.trim()}
						aria-label="Send comment">
						<SendHorizontal className="size-4" />
					</Button>
				</form>
			) : (
				<p className="text-sm text-muted-foreground">
					<Link href="/login">Sign in</Link> to comment.
				</p>
			)}

			{comments.isPending &&
				[1, 2, 3].map((key) => (
					<div
						key={key}
						className="flex gap-3">
						<Skeleton className="size-9 rounded-full" />
						<div className="flex-1 space-y-2">
							<Skeleton className="h-4 w-32" />
							<Skeleton className="h-4 w-full" />
						</div>
					</div>
				))}

			{comments.isSuccess && allComments.length === 0 && (
				<p className="text-sm text-muted-foreground">No comments yet.</p>
			)}

			<ul className="space-y-4">
				{allComments.map((comment) => (
					<CommentItem
						key={comment.id}
						kind={kind}
						comment={comment}
						isOwn={comment.username === auth?.username}
						canLike={Boolean(auth)}
						onDeleted={refresh}
					/>
				))}
			</ul>

			{comments.hasNextPage && (
				<Button
					variant="outline"
					className="w-full"
					disabled={comments.isFetchingNextPage}
					onClick={() => void comments.fetchNextPage()}>
					{comments.isFetchingNextPage ? "Loading..." : "More comments"}
				</Button>
			)}
		</section>
	)
}

function CommentItem({
	kind,
	comment,
	isOwn,
	canLike,
	onDeleted,
}: {
	kind: CommentKind
	comment: MusicComment
	isOwn: boolean
	canLike: boolean
	onDeleted: () => void
}) {
	const [hasLiked, setHasLiked] = useState(comment.hasLiked)
	const [likes, setLikes] = useState(comment.likes)
	const [deleting, setDeleting] = useState(false)

	const onLike = () => {
		setHasLiked(!hasLiked)
		setLikes(likes + (hasLiked ? -1 : 1))

		commentActions.like(kind, comment.id).catch((error) => {
			setHasLiked(hasLiked)
			setLikes(likes)
			toast.error(apiErrorMessage(error))
		})
	}

	const onDelete = () => {
		setDeleting(true)

		commentActions
			.remove(kind, comment.id)
			.then(onDeleted)
			.catch((error) => {
				setDeleting(false)
				toast.error(apiErrorMessage(error))
			})
	}

	return (
		<li className={cn("flex gap-3", deleting && "opacity-50")}>
			<UserAvatar
				name={comment.name}
				username={comment.username}
				avatar={comment.avatar}
				className="size-9"
			/>
			<div className="min-w-0 flex-1">
				<div className="flex flex-wrap items-baseline gap-x-2">
					<span className="text-sm font-semibold">{comment.name}</span>
					<span className="text-xs text-muted-foreground">
						{comment.username}
					</span>
					{(comment.decos ?? 0) > 0 && (
						<span className="inline-flex items-center gap-0.5 text-xs font-medium text-primary">
							<Award className="size-3" />
							{comment.decos}
						</span>
					)}
				</div>
				<p className="mt-0.5 whitespace-pre-line break-words text-sm">
					{comment.text}
				</p>
				<div className="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
					<span>{comment.createdAt ?? comment.created_at}</span>
					<button
						type="button"
						onClick={onLike}
						disabled={!canLike}
						aria-pressed={hasLiked}
						aria-label={hasLiked ? "Unlike comment" : "Like comment"}
						className={cn(
							"inline-flex items-center gap-1 transition-colors disabled:cursor-default",
							hasLiked ? "text-primary" : "hover:text-foreground"
						)}>
						<Heart className={cn("size-3.5", hasLiked && "fill-current")} />
						{likes > 0 && <span className="tabular-nums">{likes}</span>}
					</button>
					{isOwn && (
						<button
							type="button"
							onClick={onDelete}
							disabled={deleting}
							aria-label="Delete comment"
							className="inline-flex items-center hover:text-destructive">
							<Trash2 className="size-3.5" />
						</button>
					)}
				</div>
			</div>
		</li>
	)
}
