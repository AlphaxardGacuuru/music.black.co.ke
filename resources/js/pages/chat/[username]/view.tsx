import { echo, useEcho } from "@laravel/echo-react"
import { useQueryClient } from "@tanstack/react-query"
import { useEffect, useRef, useState } from "react"
import { ChatBubble } from "@/components/chat/chat-bubble"
import { UserAvatar } from "@/components/feed/user-avatar"
import { PageHeader } from "@/components/social/page-header"
import { SocialInput, type SocialInputValue } from "@/components/social/social-input"
import { GlassCard } from "@/components/ui/glass-card"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { apiErrorMessage } from "@/queries/feed"
import {
	socialActions,
	socialKeys,
	useConversation,
	useProfile,
} from "@/queries/social"

type PresenceMember = { id: string; name: string; username: string }

/** How long "typing..." stays up after the last keystroke whisper. */
const TYPING_TIMEOUT = 3000

export default function ChatShow({ username }: { username: string }) {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const conversation = useConversation(username)
	const user = useProfile(username)
	const [deletedIds, setDeletedIds] = useState<string[]>([])
	const [isOnline, setIsOnline] = useState(false)
	const [isTyping, setIsTyping] = useState(false)
	const typingTimer = useRef<number>(undefined)
	const lastWhisper = useRef(0)
	const bottomRef = useRef<HTMLDivElement>(null)

	const refresh = () => {
		void queryClient.invalidateQueries({
			queryKey: socialKeys.conversation(username),
		})
		void queryClient.invalidateQueries({ queryKey: socialKeys.chatThreads })
	}

	useEcho("chat-created", "NewChatEvent", refresh, [username])
	useEcho("chat-deleted", "ChatDeletedEvent", refresh, [username])

	// The "chat" presence channel: who's online and who's typing
	useEffect(() => {
		let channel: ReturnType<ReturnType<typeof echo>["join"]>

		try {
			channel = echo().join("chat")
		} catch {
			return
		}

		channel
			.here((members: PresenceMember[]) =>
				setIsOnline(members.some((member) => member.username === username))
			)
			.joining((member: PresenceMember) => {
				if (member.username === username) setIsOnline(true)
			})
			.leaving((member: PresenceMember) => {
				if (member.username === username) {
					setIsOnline(false)
					setIsTyping(false)
				}
			})
			.listenForWhisper("typing", (event: { username?: string; to?: string }) => {
				if (event.username !== username || event.to !== auth?.username) {
					return
				}

				setIsTyping(true)
				window.clearTimeout(typingTimer.current)
				typingTimer.current = window.setTimeout(
					() => setIsTyping(false),
					TYPING_TIMEOUT
				)
			})

		return () => {
			window.clearTimeout(typingTimer.current)
			echo().leave("chat")
		}
	}, [username, auth?.username])

	const onTyping = () => {
		const now = Date.now()

		// At most one whisper a second
		if (now - lastWhisper.current < 1000) {
			return
		}

		lastWhisper.current = now

		try {
			echo()
				.join("chat")
				.whisper("typing", { username: auth?.username, to: username })
		} catch {
			// Echo isn't connected; typing hints are best effort
		}
	}

	const messages = (conversation.data ?? []).filter(
		(message) => !deletedIds.includes(message.id)
	)

	// Keep the latest message in view
	useEffect(() => {
		bottomRef.current?.scrollIntoView({ block: "end" })
	}, [messages.length, isTyping])

	const onSend = ({ text, media }: SocialInputValue) =>
		socialActions
			.sendChat(username, text, media)
			.then(refresh)
			.catch((error) => {
				toast.error(apiErrorMessage(error))
				throw error
			})

	const onDelete = (id: string) => {
		setDeletedIds((ids) => [...ids, id])

		socialActions
			.deleteChat(id)
			.then((res) => res.data.message && toast.success(res.data.message))
			.catch((error) => {
				setDeletedIds((ids) => ids.filter((deletedId) => deletedId !== id))
				toast.error(apiErrorMessage(error))
			})
	}

	const isSelf = username === auth?.username

	return (
		<>
			<Head title={`${user.data?.name ?? username} | Chat | Black Music`} />
			<div className="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-4">
				<GlassCard className="sticky top-2 z-30 px-2 py-2">
					<PageHeader
						backHref="/chat"
						title={
							<span className="flex flex-col">
								<span className="truncate text-base">
									{user.data?.name ?? username}
								</span>
								<span
									className={cn(
										"truncate text-xs font-normal",
										isTyping || isOnline ? "text-primary" : "text-muted-foreground"
									)}>
									{isTyping ? "typing..." : isOnline ? "online" : username}
								</span>
							</span>
						}
						action={
							user.data && (
								<UserAvatar
									name={user.data.name}
									username={user.data.username}
									avatar={user.data.avatar}
								/>
							)
						}
					/>
				</GlassCard>

				<div className="flex-1 space-y-2">
					{conversation.isPending &&
						[1, 2, 3, 4].map((key) => (
							<Skeleton
								key={key}
								className={cn(
									"h-12 w-2/3 rounded-3xl",
									key % 2 === 0 && "ml-auto"
								)}
							/>
						))}

					{conversation.isError && (
						<p className="py-10 text-center text-sm text-muted-foreground">
							Could not load this conversation.
						</p>
					)}

					{conversation.isSuccess && messages.length === 0 && (
						<p className="py-10 text-center text-sm text-muted-foreground">
							{isSelf
								? "This is you. Pick someone else to chat with."
								: `Say hi to ${username}.`}
						</p>
					)}

					{messages.map((message) => (
						<ChatBubble
							key={message.id}
							message={message}
							isMine={message.username === auth?.username}
							onDelete={onDelete}
						/>
					))}

					{isTyping && (
						<div className="flex">
							<span className="rounded-3xl rounded-bl-md border border-border/60 bg-background/70 px-4 py-2 text-sm text-muted-foreground">
								typing...
							</span>
						</div>
					)}
					<div ref={bottomRef} />
				</div>

				{!isSelf && (
					<GlassCard className="sticky bottom-[5.5rem] z-30 mb-2 p-2 md:bottom-4">
						<SocialInput
							variant="bar"
							placeholder="Message"
							submitLabel="Send"
							mediaEndpoint="chats"
							onTyping={onTyping}
							onSubmit={onSend}
						/>
					</GlassCard>
				)}
			</div>
		</>
	)
}
