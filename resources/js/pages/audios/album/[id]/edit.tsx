import { AlbumForm } from "@/components/audio/album-form"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { useAudioAlbum } from "@/queries/audio"

/** Edit an audio album (music.black's /audio/album/edit/:id). */
export default function AudioAlbumEdit({ id }: { id: string }) {
	const { auth } = useApp()
	const query = useAudioAlbum(id)
	const album = query.data
	const isOwner = album !== undefined && album.username === auth?.username

	return (
		<div className="py-2 md:py-4">
			<Head title={`Edit ${album?.name ?? "album"} | Black Music`} />
			{query.isPending && (
				<GlassCard className="mx-auto w-full max-w-lg space-y-4 p-5 md:p-8">
					<Skeleton className="h-8 w-1/2" />
					<Skeleton className="mx-auto aspect-square w-full max-w-60 rounded-2xl" />
					<Skeleton className="h-14 w-full" />
				</GlassCard>
			)}
			{(query.isError || (album && !isOwner)) && (
				<GlassCard className="mx-auto w-full max-w-md p-8 text-center">
					<p className="font-semibold">You can only edit your own albums.</p>
					<Link
						href="/audios"
						variant="outline"
						className="mt-4">
						Back to studio
					</Link>
				</GlassCard>
			)}
			{album && isOwner && (
				<AlbumForm
					key={album.id}
					album={album}
				/>
			)}
		</div>
	)
}
