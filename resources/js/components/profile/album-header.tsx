import { Disc3 } from "lucide-react"
import { FeedImage } from "@/components/feed/feed-image"
import { mediaUrl } from "@/lib/media"
import type { Album } from "@/types/social"

/** An album's cover, kind and name, heading its songs. */
export function AlbumHeader({
	album,
	kind,
}: {
	album: Album
	kind: "Video" | "Audio"
}) {
	return (
		<div className="flex items-center gap-3">
			<div className="relative flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-muted text-muted-foreground">
				<Disc3 className="size-6" />
				<FeedImage
					src={mediaUrl(album.cover)}
					className="absolute inset-0 size-full object-cover"
				/>
			</div>
			<div className="min-w-0">
				<p className="text-xs text-muted-foreground">{kind} album</p>
				<p className="truncate text-lg font-bold">{album.name}</p>
				<p className="text-xs text-muted-foreground">{album.released ?? album.createdAt}</p>
			</div>
		</div>
	)
}
