import { useEcho } from "@laravel/echo-react"
import { useQuery, useQueryClient } from "@tanstack/react-query"
import { SquarePen } from "lucide-react"
import { ChatThreadRow } from "@/components/chat/chat-thread-row"
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
			<div className="mx-auto w-full max-w-2xl space-y-4 px-4 py-6">
				<PageHeader
					title="Chat"
					action={
						<Link
							href="/chat/new"
							variant="solid"
							size="sm"
							className="gap-1.5">
							<SquarePen className="size-4" />
							New chat
						</Link>
					}
				/>

				<GlassCard className="p-2">
					{threads.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="flex items-center gap-3 px-3 py-2.5">
								<Skeleton className="size-12 rounded-full" />
								<div className="flex-1 space-y-2">
									<Skeleton className="h-4 w-1/3" />
									<Skeleton className="h-3 w-2/3" />
								</div>
							</div>
						))}

					{threads.isError && (
						<p className="p-6 text-center text-sm text-muted-foreground">
							Could not load your chats.
						</p>
					)}

					{threads.data?.length === 0 && (
						<ChatThreadRow
							username="@blackmusic"
							name="Black Music"
							avatar={blackMusic.data?.avatar}
							preview="Need help? Start a conversation."
						/>
					)}

					{threads.data?.map((thread) => (
						<ChatThreadRow
							key={thread.id}
							username={thread.username}
							name={thread.name}
							avatar={thread.avatar}
							preview={thread.text}
							fromMe={thread.to === thread.username}
							hasMedia={Boolean(thread.hasMedia)}
							time={thread.createdAt}
						/>
					))}
				</GlassCard>
			</div>
		</>
	)
}
