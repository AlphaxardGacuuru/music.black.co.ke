import { Heart, Play } from "lucide-react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Link } from "@/components/ui/link"
import { mediaUrl } from "@/lib/media"
import type { Karaoke } from "@/types/karaoke"

/**
 * A karaoke as a small portrait tile (music.black's KaraokeMedia), for grids
 * like the library's saved karaokes. Opens the karaoke in the feed.
 */
export function KaraokeTile({ karaoke }: { karaoke: Karaoke }) {
	return (
		<div className="min-w-0">
			<Link
				href={`/karaoke/${karaoke.id}/view`}
				variant="unstyled"
				className="group relative block aspect-[9/16] overflow-hidden rounded-2xl bg-muted">
				<video
					src={`${mediaUrl(karaoke.karaoke)}#t=0.5`}
					poster={karaoke.audioThumbnail}
					preload="metadata"
					muted
					playsInline
					className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
				/>
				<span className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" />
				<span className="absolute inset-0 m-auto flex size-10 items-center justify-center rounded-full bg-black/40 text-white opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100">
					<Play className="size-5 translate-x-px fill-white" />
				</span>
				<span className="absolute inset-x-2 bottom-2 flex items-center gap-1 text-xs font-semibold text-white">
					<Heart className="size-3.5" />
					{karaoke.likes}
					<span className="ml-1 truncate font-normal text-white/80">
						{karaoke.audioName}
					</span>
				</span>
			</Link>
			<div className="mt-2 flex items-center gap-2">
				<UserAvatar
					name={karaoke.name}
					username={karaoke.username}
					avatar={karaoke.avatar}
					className="size-7 text-[10px]"
				/>
				<div className="min-w-0">
					<p className="truncate text-sm font-medium leading-tight">{karaoke.name}</p>
					<p className="truncate text-xs text-muted-foreground">{karaoke.username}</p>
				</div>
			</div>
		</div>
	)
}
