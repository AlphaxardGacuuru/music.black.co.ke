import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import { PageHeader } from "@/components/social/page-header"
import { SocialInput, type SocialInputValue } from "@/components/social/social-input"
import { GlassCard } from "@/components/ui/glass-card"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { apiErrorMessage, feedKeys } from "@/queries/feed"
import { socialActions } from "@/queries/social"

export default function PostCreate() {
	const navigate = useNavigate()
	const queryClient = useQueryClient()

	const onSubmit = ({ text, media, poll }: SocialInputValue) =>
		socialActions
			.createPost({
				text,
				media,
				para1: poll[0],
				para2: poll[1],
				para3: poll[2],
				para4: poll[3],
				para5: poll[4],
			})
			.then((res) => {
				toast.success(res.data.message ?? "Post posted")
				void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
				void navigate({ to: "/" })
			})
			.catch((error) => {
				toast.error(apiErrorMessage(error))
				throw error
			})

	return (
		<>
			<Head title="Create post | Black Music" />
			<div className="mx-auto w-full max-w-xl space-y-4">
				<PageHeader title="Create post" />
				<GlassCard className="p-4 sm:p-5">
					<SocialInput
						placeholder="What's on your mind?"
						submitLabel="Post"
						mediaEndpoint="posts"
						allowPoll
						autoFocus
						onSubmit={onSubmit}
					/>
				</GlassCard>
			</div>
		</>
	)
}
