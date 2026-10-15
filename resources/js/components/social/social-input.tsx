import EmojiPicker, { Theme, type EmojiClickData } from "emoji-picker-react"
import { BarChart3, ImagePlus, Plus, SendHorizontal, Smile, X } from "lucide-react"
import { useRef, useState, type FormEvent, type KeyboardEvent } from "react"
import { FileUpload } from "@/components/music/file-upload"
import { Button } from "@/components/ui/button"
import {
	Popover,
	PopoverContent,
	PopoverTrigger,
} from "@/components/ui/popover"
import { Spinner } from "@/components/ui/spinner"
import { useApp } from "@/contexts/AppContext"
import { cn } from "@/lib/utils"
import { useAllArtists } from "@/queries/social"

export type SocialInputValue = {
	text: string
	/** The stored upload path, e.g. "post-media/abc.jpg". */
	media?: string
	/** Up to five non-empty poll options, in order. */
	poll: string[]
}

type Props = {
	placeholder: string
	/** Pre-fills the text, e.g. when editing a post. */
	initialText?: string
	submitLabel?: string
	/** FilePond endpoint for an attached image ("posts" or "chats"); omit to hide. */
	mediaEndpoint?: "posts" | "chats"
	allowPoll?: boolean
	/** "page" is the roomy create/edit form, "bar" the chat composer. */
	variant?: "page" | "bar"
	/** Keep the text after a successful submit (editing). */
	keepText?: boolean
	autoFocus?: boolean
	onTyping?: () => void
	/** Resolve to clear the input, reject to keep it. */
	onSubmit: (value: SocialInputValue) => Promise<unknown>
	className?: string
}

const POLL_SLOTS = 5

/**
 * The composer for posts and chats (OLD SocialMediaInput): text with an
 * emoji picker, @mentions of musicians, an optional image and an optional
 * poll of up to five options.
 */
export function SocialInput({
	placeholder,
	initialText = "",
	submitLabel = "Send",
	mediaEndpoint,
	allowPoll = false,
	variant = "page",
	keepText = false,
	autoFocus = false,
	onTyping,
	onSubmit,
	className,
}: Props) {
	const [text, setText] = useState(initialText)
	const [media, setMedia] = useState<string>()
	const [showMedia, setShowMedia] = useState(false)
	const [showPoll, setShowPoll] = useState(false)
	const [poll, setPoll] = useState<string[]>(Array(POLL_SLOTS).fill(""))
	const [emojiOpen, setEmojiOpen] = useState(false)
	const [sending, setSending] = useState(false)
	const [uploadKey, setUploadKey] = useState(0)
	const textareaRef = useRef<HTMLTextAreaElement>(null)

	const mention = text.match(/(?:^|\s)@([\w.]*)$/)?.[1]

	const canSend = text.trim().length > 0 && !sending

	const insertEmoji = (emoji: EmojiClickData) => {
		const el = textareaRef.current
		const start = el?.selectionStart ?? text.length
		const end = el?.selectionEnd ?? text.length

		setText(text.slice(0, start) + emoji.emoji + text.slice(end))
	}

	const addMention = (username: string) => {
		setText(text.replace(/@[\w.]*$/, `${username} `))
		textareaRef.current?.focus()
	}

	const reset = () => {
		if (!keepText) {
			setText("")
		}
		setMedia(undefined)
		setShowMedia(false)
		setShowPoll(false)
		setPoll(Array(POLL_SLOTS).fill(""))
		setUploadKey((key) => key + 1)
	}

	const submit = (event?: FormEvent) => {
		event?.preventDefault()

		if (!canSend) {
			return
		}

		setSending(true)

		onSubmit({
			text: text.trim(),
			media,
			poll: showPoll ? poll.map((p) => p.trim()).filter(Boolean) : [],
		})
			.then(reset)
			.catch(() => undefined)
			.finally(() => setSending(false))
	}

	const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
		// In the chat bar, Enter sends and Shift+Enter adds a line
		if (variant === "bar" && event.key === "Enter" && !event.shiftKey) {
			event.preventDefault()
			submit()
		}
	}

	const visiblePollSlots = Math.min(
		POLL_SLOTS,
		Math.max(2, poll.filter(Boolean).length + 1)
	)

	return (
		<form
			onSubmit={submit}
			autoComplete="off"
			className={cn("space-y-2", className)}>
			{mention !== undefined && (
				<MentionPicker
					query={mention}
					onPick={addMention}
				/>
			)}

			<div
				className={cn(
					"flex gap-1",
					variant === "bar" ? "items-center" : "flex-col"
				)}>
				<textarea
					ref={textareaRef}
					value={text}
					onChange={(event) => {
						setText(event.target.value)
						onTyping?.()
					}}
					onKeyDown={onKeyDown}
					placeholder={placeholder}
					autoFocus={autoFocus}
					rows={variant === "bar" ? 1 : 4}
					maxLength={5000}
					className={cn(
						"w-full resize-none rounded-2xl border border-border bg-background/60 px-4 py-2.5 text-[15px] leading-relaxed outline-none placeholder:text-muted-foreground focus:border-primary/60 focus:ring-2 focus:ring-primary/20",
						variant === "bar" ? "max-h-32 min-h-10 flex-1" : "min-h-28"
					)}
				/>

				<div className="flex shrink-0 items-center gap-1">
					<Popover
						open={emojiOpen}
						onOpenChange={setEmojiOpen}>
						<PopoverTrigger asChild>
							<Button
								type="button"
								variant="ghost"
								size="icon"
								aria-label="Add emoji">
								<Smile className="size-5" />
							</Button>
						</PopoverTrigger>
						<PopoverContent
							align="start"
							side="top"
							className="w-auto border-none bg-transparent p-0 shadow-none">
							<EmojiPicker
								onEmojiClick={insertEmoji}
								theme={Theme.AUTO}
								lazyLoadEmojis
								width={320}
								height={380}
							/>
						</PopoverContent>
					</Popover>

					{mediaEndpoint && (
						<Button
							type="button"
							variant={showMedia ? "default" : "ghost"}
							size="icon"
							aria-label="Attach image"
							aria-pressed={showMedia}
							disabled={showPoll}
							onClick={() => setShowMedia(!showMedia)}>
							<ImagePlus className="size-5" />
						</Button>
					)}

					{allowPoll && (
						<Button
							type="button"
							variant={showPoll ? "default" : "ghost"}
							size="icon"
							aria-label="Add poll"
							aria-pressed={showPoll}
							disabled={Boolean(media)}
							onClick={() => setShowPoll(!showPoll)}>
							<BarChart3 className="size-5" />
						</Button>
					)}

					<Button
						type="submit"
						size={variant === "bar" ? "icon" : "default"}
						disabled={!canSend}
						aria-label={submitLabel}
						className={cn(variant === "page" && "ml-auto px-6")}>
						{sending ? (
							<Spinner />
						) : variant === "bar" ? (
							<SendHorizontal className="size-4" />
						) : (
							submitLabel
						)}
					</Button>
				</div>
			</div>

			{mediaEndpoint && showMedia && (
				<FileUpload
					key={uploadKey}
					endpoint={mediaEndpoint}
					field="filepond-media"
					acceptedFileTypes={["image/*"]}
					maxFileSize="20MB"
					label='Drop an image or <span class="filepond--label-action">browse</span>'
					onUploaded={setMedia}
					onRemoved={() => setMedia(undefined)}
				/>
			)}

			{allowPoll && showPoll && (
				<div className="space-y-2 rounded-2xl border border-border/60 p-3">
					<div className="flex items-center justify-between">
						<p className="text-sm font-semibold">Poll</p>
						<Button
							type="button"
							variant="ghost"
							size="icon"
							className="size-7"
							aria-label="Remove poll"
							onClick={() => {
								setShowPoll(false)
								setPoll(Array(POLL_SLOTS).fill(""))
							}}>
							<X className="size-4" />
						</Button>
					</div>
					{poll.slice(0, visiblePollSlots).map((value, index) => (
						<input
							key={index}
							value={value}
							maxLength={100}
							placeholder={`Option ${index + 1}`}
							onChange={(event) =>
								setPoll(
									poll.map((p, i) => (i === index ? event.target.value : p))
								)
							}
							className="h-10 w-full rounded-full border border-border bg-background/60 px-4 text-sm outline-none focus:border-primary/60"
						/>
					))}
					{visiblePollSlots < POLL_SLOTS && (
						<p className="flex items-center gap-1 text-xs text-muted-foreground">
							<Plus className="size-3" />
							Fill an option to add another (up to five). Voting stays open
							for 24 hours.
						</p>
					)}
				</div>
			)}
		</form>
	)
}

/** Musicians matching the "@..." being typed, as in OLD's mention picker. */
function MentionPicker({
	query,
	onPick,
}: {
	query: string
	onPick: (username: string) => void
}) {
	const { auth } = useApp()
	const artists = useAllArtists()
	const needle = query.toLowerCase()

	const matches = (artists.data ?? [])
		.filter(
			(artist) =>
				artist.username !== auth?.username &&
				(artist.username.toLowerCase().includes(needle) ||
					artist.name.toLowerCase().includes(needle))
		)
		.slice(0, 6)

	if (matches.length === 0) {
		return null
	}

	return (
		<div className="max-h-52 overflow-y-auto rounded-2xl border border-border/60 bg-background/90 p-1 backdrop-blur">
			{matches.map((artist) => (
				<button
					key={artist.id}
					type="button"
					onClick={() => onPick(artist.username)}
					className="flex w-full items-center gap-2 rounded-xl px-2 py-1.5 text-left hover:bg-accent">
					<img
						src={artist.avatar}
						alt=""
						className="size-7 rounded-full object-cover"
					/>
					<span className="truncate text-sm font-medium">{artist.name}</span>
					<span className="truncate text-xs text-muted-foreground">
						{artist.username}
					</span>
				</button>
			))}
		</div>
	)
}
