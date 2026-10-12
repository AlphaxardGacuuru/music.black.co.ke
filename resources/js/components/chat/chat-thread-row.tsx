import { ImageIcon } from "lucide-react"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Link } from "@/components/ui/link"
import { useInitials } from "@/hooks/use-initials"

type Props = {
	username: string
	name: string
	avatar?: string
	/** The latest message, or a hint like "Need help? Start a conversation." */
	preview: string
	/** Prefix the preview with "You:" when the viewer sent it. */
	fromMe?: boolean
	hasMedia?: boolean
	time?: string
	/** What to link to (defaults to the conversation). */
	href?: string
	/** Shown on the right instead of the time, e.g. the account type. */
	aside?: string
}

/** One person in the chat list or the "new chat" picker. */
export function ChatThreadRow({
	username,
	name,
	avatar,
	preview,
	fromMe,
	hasMedia,
	time,
	href,
	aside,
}: Props) {
	const getInitials = useInitials()

	return (
		<Link
			href={href ?? `/chat/show/${username}`}
			variant="unstyled"
			className="flex items-center gap-3 rounded-2xl px-3 py-2.5 transition-colors hover:bg-accent/60">
			<Avatar className="size-12 shrink-0">
				<AvatarImage
					src={avatar}
					alt={name}
					className="object-cover"
				/>
				<AvatarFallback className="bg-primary/15 text-primary">
					{getInitials(name)}
				</AvatarFallback>
			</Avatar>
			<div className="min-w-0 flex-1">
				<div className="flex items-baseline gap-2">
					<p className="truncate font-semibold">{name}</p>
					<p className="truncate text-xs text-muted-foreground">{username}</p>
					{(time || aside) && (
						<p className="ml-auto shrink-0 text-[11px] text-muted-foreground">
							{time ?? aside}
						</p>
					)}
				</div>
				<p className="flex items-center gap-1 truncate text-sm text-muted-foreground">
					{fromMe && <span className="shrink-0">You:</span>}
					{hasMedia && <ImageIcon className="size-3.5 shrink-0" />}
					<span className="truncate">{preview}</span>
				</p>
			</div>
		</Link>
	)
}
