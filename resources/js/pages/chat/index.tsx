import { useEcho } from "@laravel/echo-react"
import { useQuery, useQueryClient } from "@tanstack/react-query"
import { SquarePen } from "lucide-react"
import { ChatThreadRow } from "@/components/chat/chat-thread-row"
import { FloatingActions } from "@/components/floating-actions"
import { PageHeader } from "@/components/social/page-header"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import Axios from "@/lib/axios"
import { Head } from "@/lib/spa"
import { socialKeys, useChatThreads } from "@/queries/social"
import type { Profile } from "@/types/social"

const PLACEHOLDERS = [1, 2, 3, 4, 5]

export default function ChatIndex() {
	const queryClient = useQueryClient()
	const threads = useChatThreads()

	// The help thread shown before the first conversation
	const blackMusic = useQuery({
		queryKey: socialKeys.profile("@blackmusic"),
		enabled: threads.isSuccess && threads.data.length === 0,
		queryFn: () =>
			Axios.get<{ data: Profile }>("api/users/@blackmusic").then(
				(res) => res.data.data
			),
	})

	const refresh = () =>
		void queryClient.invalidateQueries({ queryKey: socialKeys.chatThreads })

	useEcho("chat-created", "NewChatEvent", refresh)
	useEcho("chat-deleted", "ChatDeletedEvent", refresh)

	return (
		<>
			<Head title="Chat | Black Music" />
			{/* Bottom padding keeps the last thread clear of the floating button. */}
			<div className="mx-auto w-full max-w-2xl space-y-4 pb-20">
				<PageHeader title="Chat" />

				<div className="space-y-2">
					{threads.isPending &&
						PLACEHOLDERS.map((key) => (
							<GlassCard
								key={key}
								className="flex items-center gap-3 px-3 py-2.5">
								<Skeleton className="size-12 rounded-full" />
								<div className="flex-1 space-y-2">
									<Skeleton className="h-4 w-1/3" />
									<Skeleton className="h-3 w-2/3" />
								</div>
							</GlassCard>
						))}

					{threads.isError && (
						<GlassCard className="p-6 text-center text-sm text-muted-foreground">
							Could not load your chats.
						</GlassCard>
					)}

					{threads.data?.length === 0 && (
						<GlassCard className="p-1">
							<ChatThreadRow
								username="@blackmusic"
								name="Black Music"
								avatar={blackMusic.data?.avatar}
								preview="Need help? Start a conversation."
							/>
						</GlassCard>
					)}

					{threads.data?.map((thread) => (
						<GlassCard
							key={thread.id}
							className="p-1">
							<ChatThreadRow
								username={thread.username}
								name={thread.name}
								avatar={thread.avatar}
								preview={thread.text}
								fromMe={thread.to === thread.username}
								hasMedia={Boolean(thread.hasMedia)}
								time={thread.createdAt}
							/>
						</GlassCard>
					))}
				</div>
			</div>

			<FloatingActions>
				<Link
					href="/chat/new"
					variant="solid"
					size="lg"
					className="h-12 gap-2 shadow-lg shadow-primary/30">
					<SquarePen className="size-5" />
					New chat
				</Link>
			</FloatingActions>
		</>
	)
}
