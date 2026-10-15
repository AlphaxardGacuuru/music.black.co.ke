import { Film } from "lucide-react"
import { FeedImage } from "@/components/feed/feed-image"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { VideoForm } from "@/components/video/video-form"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { mediaUrl } from "@/lib/media"
import { useVideo } from "@/queries/video"
import { ADMIN_EMAIL } from "@/middleware/auth"

export default function VideoEdit({ id }: { id: string }) {
	const { auth } = useApp()
	const video = useVideo(id)

	const isOwner =
		video.data &&
		(video.data.username === auth?.username || auth?.username === "@blackmusic" || auth?.email === ADMIN_EMAIL)

	return (
		<div className="mx-auto w-full max-w-2xl space-y-6">
			<Head title={`Edit ${video.data?.name ?? "video"} | Black Music`} />

			{video.isPending && (
				<div className="space-y-4">
					<Skeleton className="h-24 w-full rounded-2xl" />
					<Skeleton className="h-96 w-full rounded-3xl" />
				</div>
			)}

			{!video.isPending && !video.data && (
				<p className="py-12 text-center text-sm text-muted-foreground">
					This video could not be found.
				</p>
			)}

			{video.data && !isOwner && (
				<p className="py-12 text-center text-sm text-muted-foreground">
					You can only edit your own videos.
				</p>
			)}

			{video.data && isOwner && (
				<>
					<header className="flex items-center gap-4">
						<Link
							href={`/videos/${video.data.id}/view`}
							variant="unstyled"
							className="relative aspect-video w-32 shrink-0 overflow-hidden rounded-xl bg-muted">
							<Film className="absolute inset-0 m-auto size-6 text-muted-foreground/40" />
							<FeedImage
								src={mediaUrl(video.data.thumbnail)}
								className="relative size-full object-cover"
							/>
						</Link>
						<div className="min-w-0">
							<p className="text-sm text-muted-foreground">Edit video</p>
							<h1 className="truncate text-xl font-bold">{video.data.name}</h1>
							<p className="truncate text-sm text-muted-foreground">
								{video.data.username}
								{video.data.ft && ` ft. ${video.data.ft}`} ·{" "}
								{video.data.released}
							</p>
						</div>
					</header>
					<VideoForm
						key={video.data.id}
						mode="edit"
						video={video.data}
					/>
				</>
			)}
		</div>
	)
}
