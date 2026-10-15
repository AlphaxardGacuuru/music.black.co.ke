import { useEcho } from "@laravel/echo-react"
import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import { ArrowUp } from "lucide-react"
import { useState } from "react"
import { PostCard } from "@/components/feed/post-card"
import { CommentSection } from "@/components/music/comment-section"
import { PageHeader } from "@/components/social/page-header"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { commentKeys } from "@/queries/comments"
import { feedKeys } from "@/queries/feed"
import { socialKeys, usePost } from "@/queries/social"

export const isPublic = true

export default function PostShow({ id }: { id: string }) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const post = usePost(id)
	const [hasNewComments, setHasNewComments] = useState(false)

	const refreshPost = () =>
		void queryClient.invalidateQueries({ queryKey: socialKeys.post(id) })

	const onShowNewComments = () => {
		setHasNewComments(false)
		void queryClient.invalidateQueries({
			queryKey: commentKeys.list("post", id),
		})
		refreshPost()
	}

	return (
		<>
			<Head title="Post | Black Music" />
			{auth && <NewCommentsListener onNew={() => setHasNewComments(true)} />}

			<div className="mx-auto w-full max-w-xl space-y-4">
				<PageHeader title="Post" />

				{hasNewComments && (
					<Button
						onClick={onShowNewComments}
						className="fixed left-1/2 top-4 z-50 -translate-x-1/2 gap-2 shadow-lg shadow-primary/30">
						<ArrowUp className="size-4" />
						New comments
					</Button>
				)}

				{post.isPending && (
					<GlassCard className="space-y-3 p-5">
						<div className="flex items-center gap-3">
							<Skeleton className="size-10 rounded-full" />
							<Skeleton className="h-4 w-40" />
						</div>
						<Skeleton className="h-4 w-full" />
						<Skeleton className="h-4 w-2/3" />
					</GlassCard>
				)}

				{post.isError && (
					<GlassCard className="p-8 text-center text-sm text-muted-foreground">
						This post was deleted or never existed.
					</GlassCard>
				)}

				{post.data && (
					<>
						<PostCard
							// Remount after a refetch so likes and votes show fresh counts
							key={post.dataUpdatedAt}
							post={post.data}
							onDeleted={() => {
								void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
								void navigate({ to: "/" })
							}}
						/>

						<GlassCard className="p-4 sm:p-5">
							<CommentSection
								kind="post"
								parentId={id}
								onChange={refreshPost}
							/>
						</GlassCard>
					</>
				)}
			</div>
		</>
	)
}

/** Signed-in viewers hear about new comments (OLD "post-commented"). */
function NewCommentsListener({ onNew }: { onNew: () => void }) {
	useEcho("post-commented", "PostCommentedEvent", onNew)

	return null
}
