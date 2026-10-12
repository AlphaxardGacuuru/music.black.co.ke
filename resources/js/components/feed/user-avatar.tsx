import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Link } from "@/components/ui/link"
import { useInitials } from "@/hooks/use-initials"
import { cn } from "@/lib/utils"

type Props = {
	name: string
	username: string
	avatar?: string
	className?: string
}

/** A musician or fan's avatar that links to their profile. */
export function UserAvatar({ name, username, avatar, className }: Props) {
	const getInitials = useInitials()

	return (
		<Link
			href={`/profile/show/${username}`}
			variant="unstyled"
			className="shrink-0">
			<Avatar className={cn("size-10 text-sm", className)}>
				<AvatarImage
					src={avatar}
					alt={name}
					className="object-cover"
				/>
				<AvatarFallback className="bg-primary/15 text-primary">
					{getInitials(name)}
				</AvatarFallback>
			</Avatar>
		</Link>
	)
}
