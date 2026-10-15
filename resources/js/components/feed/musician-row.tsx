import { useQueryClient } from "@tanstack/react-query"
import { Check } from "lucide-react"
import { useState } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import { useApp } from "@/contexts/AppContext"
import toast from "@/lib/toast"
import { apiErrorMessage, feedActions, feedKeys } from "@/queries/feed"
import type { Artist } from "@/types/music"

/**
 * A "musician to follow" suggestion. As on music.black, fans can only follow
 * a musician once they have bought at least one of their songs.
 */
export function MusicianRow({ artist }: { artist: Artist }) {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const [hasFollowed, setHasFollowed] = useState(artist.hasFollowed)

	const canFollow = artist.hasBought1 || auth?.username === "@blackmusic"

	const onFollow = () => {
		if (!canFollow) {
			toast.error(
				`You must have bought at least one song by ${artist.username}`
			)

			return
		}

		setHasFollowed(!hasFollowed)

		feedActions
			.follow(artist.username)
			.then((res) => {
				if (res.data.message) {
					toast.success(res.data.message)
				}
				void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
				void queryClient.invalidateQueries({ queryKey: feedKeys.stories })
			})
			.catch((error) => {
				setHasFollowed(hasFollowed)
				toast.error(apiErrorMessage(error))
			})
	}

	return (
		<div className="flex items-center gap-3 py-2">
			<UserAvatar
				name={artist.name}
				username={artist.username}
				avatar={artist.avatar}
				className="size-9"
			/>
			<Link
				href={`/profile/${artist.username}/view`}
				variant="unstyled"
				className="min-w-0 flex-1">
				<p className="truncate text-sm font-semibold">{artist.name}</p>
				<p className="truncate text-xs text-muted-foreground">
					{artist.username}
				</p>
			</Link>
			<Button
				size="sm"
				variant={hasFollowed ? "secondary" : "outline"}
				className="h-7 gap-1 px-3 text-xs"
				onClick={onFollow}>
				{hasFollowed ? (
					<>
						Followed
						<Check className="size-3" />
					</>
				) : (
					"Follow"
				)}
			</Button>
		</div>
	)
}
