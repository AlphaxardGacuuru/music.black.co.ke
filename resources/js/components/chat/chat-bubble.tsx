import { Trash2 } from "lucide-react"
import { useState } from "react"
import { FeedImage } from "@/components/feed/feed-image"
import { Button } from "@/components/ui/button"
import { cn } from "@/lib/utils"
import type { ChatMessage } from "@/types/social"

type Props = {
	message: ChatMessage
	isMine: boolean
	onDelete: (id: string) => void
}

/** One message. Tapping your own reveals a delete button (as in OLD). */
export function ChatBubble({ message, isMine, onDelete }: Props) {
	const [showDelete, setShowDelete] = useState(false)

	return (
		<div
			className={cn(
				"flex items-center gap-2",
				isMine ? "flex-row-reverse" : "flex-row"
			)}>
			<button
				type="button"
				disabled={!isMine}
				onClick={() => setShowDelete(!showDelete)}
				className={cn(
					"max-w-[80%] overflow-hidden rounded-3xl px-4 py-2 text-left text-[15px] leading-relaxed disabled:cursor-default sm:max-w-[70%]",
					isMine
						? "rounded-br-md bg-primary text-primary-foreground"
						: "rounded-bl-md border border-border/60 bg-background/70 backdrop-blur"
				)}>
				{message.media && (
					<FeedImage
						src={message.media}
						alt="Attached image"
						className="-mx-2 mb-2 mt-1 max-h-72 w-[calc(100%+1rem)] max-w-none rounded-2xl object-cover"
					/>
				)}
				<span className="whitespace-pre-line break-words">{message.text}</span>
				<span
					className={cn(
						"mt-0.5 block text-right text-[10px]",
						isMine ? "text-primary-foreground/70" : "text-muted-foreground"
					)}>
					{message.createdAt}
				</span>
			</button>

			{isMine && showDelete && (
				<Button
					variant="ghost"
					size="icon"
					className="size-8 text-destructive hover:text-destructive"
					aria-label="Delete message"
					onClick={() => onDelete(message.id)}>
					<Trash2 className="size-4" />
				</Button>
			)}
		</div>
	)
}
