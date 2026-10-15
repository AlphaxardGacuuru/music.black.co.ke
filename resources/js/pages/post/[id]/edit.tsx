import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import { FeedImage } from "@/components/feed/feed-image"
import { PageHeader } from "@/components/social/page-header"
import { SocialInput, type SocialInputValue } from "@/components/social/social-input"
import { GlassCard } from "@/components/ui/glass-card"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { apiErrorMessage, feedKeys } from "@/queries/feed"
import { socialActions, socialKeys, usePost } from "@/queries/social"

/** Only the text of a post can change (OLD and the API both say so). */
export default function PostEdit({ id }: { id: string }) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const post = usePost(id)

	const onSubmit = ({ text }: SocialInputValue) =>
		socialActions
			.updatePost(id, text)
			.then((res) => {
				toast.success(res.data.message ?? "Post edited")
				void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
				void queryClient.invalidateQueries({ queryKey: socialKeys.post(id) })
				void navigate({ to: `/post/${id}/view` })
			})
			.catch((error) => {
				toast.error(apiErrorMessage(error))
				throw error
			})

	const isOwner = post.data && post.data.username === auth?.username

	return (
		<>
			<Head title="Edit post | Black Music" />
			<div className="mx-auto w-full max-w-xl space-y-4">
				<PageHeader
					title="Edit post"
					backHref={`/post/${id}/view`}
				/>
				<GlassCard className="space-y-4 p-4 sm:p-5">
					{post.isPending && <Skeleton className="h-28 w-full rounded-2xl" />}
					{post.isError && (
						<p className="text-sm text-muted-foreground">
							This post could not be found.
						</p>
					)}
					{post.data && !isOwner && (
						<p className="text-sm text-muted-foreground">
							You can only edit your own posts.
						</p>
					)}
					{post.data && isOwner && (
						<>
							<SocialInput
								placeholder="What's on your mind?"
								initialText={post.data.text ?? ""}
								submitLabel="Save"
								keepText
								autoFocus
								onSubmit={onSubmit}
							/>
							{post.data.media && (
								<div className="overflow-hidden rounded-2xl border border-border/60">
									<FeedImage
										src={post.data.media}
										className="max-h-80 w-full object-cover"
									/>
								</div>
							)}
						</>
					)}
				</GlassCard>
			</div>
		</>
	)
}
