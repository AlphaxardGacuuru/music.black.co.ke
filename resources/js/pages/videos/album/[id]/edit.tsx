import { FeedImage } from "@/components/feed/feed-image"
import { Skeleton } from "@/components/ui/skeleton"
import { AlbumForm } from "@/components/video/album-form"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { mediaUrl } from "@/lib/media"
import { useVideoAlbum } from "@/queries/video"
import { ADMIN_EMAIL } from "@/middleware/auth"

export default function VideoAlbumEdit({ id }: { id: string }) {
	const { auth } = useApp()
	const album = useVideoAlbum(id)

	const isOwner =
		album.data &&
		(album.data.username === auth?.username || auth?.username === "@blackmusic" || auth?.email === ADMIN_EMAIL)

	return (
		<div className="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
			<Head title={`Edit ${album.data?.name ?? "album"} | Black Music`} />

			{album.isPending && (
				<div className="space-y-4">
					<Skeleton className="h-20 w-full rounded-2xl" />
					<Skeleton className="h-96 w-full rounded-3xl" />
				</div>
			)}

			{!album.isPending && !album.data && (
				<p className="py-12 text-center text-sm text-muted-foreground">
					This album could not be found.
				</p>
			)}

			{album.data && !isOwner && (
				<p className="py-12 text-center text-sm text-muted-foreground">
					You can only edit your own albums.
				</p>
			)}

			{album.data && isOwner && (
				<>
					<header className="flex items-center gap-4">
						<div className="size-16 shrink-0 overflow-hidden rounded-xl bg-muted">
							<FeedImage
								src={mediaUrl(album.data.cover)}
								className="size-full object-cover"
							/>
						</div>
						<div className="min-w-0">
							<p className="text-sm text-muted-foreground">Edit video album</p>
							<h1 className="truncate text-xl font-bold">{album.data.name}</h1>
							<p className="text-sm text-muted-foreground">
								Released {album.data.released}
							</p>
						</div>
					</header>
					<AlbumForm
						key={album.data.id}
						mode="edit"
						album={album.data}
					/>
				</>
			)}
		</div>
	)
}
