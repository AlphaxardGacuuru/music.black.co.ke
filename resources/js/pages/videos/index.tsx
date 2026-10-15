import {
	Download,
	Film,
	FolderPlus,
	Heart,
	Music,
	Pencil,
	Upload,
} from "lucide-react"
import { FeedImage } from "@/components/feed/feed-image"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { MusicianGate } from "@/components/video/musician-gate"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { mediaUrl } from "@/lib/media"
import {
	useArtistBoughtVideos,
	useArtistVideoAlbums,
	useArtistVideos,
} from "@/queries/video"
import type { VideoAlbum, VideoDetail } from "@/types/video"
import { VIDEO_ARTIST_SHARE } from "@/types/video"

export default function VideoStudio() {
	return (
		<MusicianGate>
			<Head title="Video studio | Black Music" />
			<Studio />
		</MusicianGate>
	)
}

function Studio() {
	const { auth } = useApp()
	const albums = useArtistVideoAlbums(auth?.username)
	const videos = useArtistVideos(auth?.username)
	const sales = useArtistBoughtVideos(auth?.username)

	const downloads = sales.data?.length ?? 0
	const stats = [
		{ label: "Videos", value: videos.data?.length },
		{
			label: "Video albums",
			value: albums.data?.filter((album) => album.name !== "Singles").length,
		},
		{ label: "Downloads", value: sales.data ? downloads : undefined },
		{
			label: "Revenue",
			value: sales.data
				? `KES ${(downloads * VIDEO_ARTIST_SHARE).toLocaleString()}`
				: undefined,
		},
	]

	return (
		<div className="mx-auto w-full max-w-6xl space-y-6 lg:px-6">
			<header className="flex flex-wrap items-end justify-between gap-4">
				<div>
					<p className="text-sm text-muted-foreground">Studio</p>
					<h1 className="text-3xl font-bold">Videos</h1>
				</div>
				<div className="flex flex-wrap gap-2">
					<Link
						href="/audios"
						variant="outline"
						size="sm">
						<Music className="size-4" />
						Go to audios
					</Link>
					<Link
						href="/videos/album/create"
						variant="outline"
						size="sm">
						<FolderPlus className="size-4" />
						Create album
					</Link>
					<Link
						href="/videos/create"
						variant="solid"
						size="sm">
						<Upload className="size-4" />
						Upload video
					</Link>
				</div>
			</header>

			<div className="grid grid-cols-2 gap-3 md:grid-cols-4">
				{stats.map((stat) => (
					<GlassCard
						key={stat.label}
						className="p-4">
						<p className="text-xs text-muted-foreground">{stat.label}</p>
						{stat.value === undefined ? (
							<Skeleton className="mt-1.5 h-7 w-16" />
						) : (
							<p
								className={
									stat.label === "Revenue"
										? "mt-0.5 text-xl font-bold tabular-nums text-primary"
										: "mt-0.5 text-xl font-bold tabular-nums"
								}>
								{stat.value}
							</p>
						)}
					</GlassCard>
				))}
			</div>

			{(albums.isPending || videos.isPending) && (
				<div className="space-y-4">
					<Skeleton className="h-24 w-full rounded-3xl" />
					<Skeleton className="h-48 w-full rounded-3xl" />
				</div>
			)}

			{albums.data?.length === 0 && (
				<p className="text-sm text-muted-foreground">
					No albums yet. Create one, then upload your first video.
				</p>
			)}

			{albums.data &&
				videos.data &&
				albums.data.map((album) => (
					<AlbumSection
						key={album.id}
						album={album}
						videos={videos.data.filter(
							(video) => video.videoAlbumId === album.id
						)}
					/>
				))}
		</div>
	)
}

function AlbumSection({
	album,
	videos,
}: {
	album: VideoAlbum
	videos: VideoDetail[]
}) {
	const isSingles = album.name === "Singles"

	const cover = (
		<div className="size-20 shrink-0 overflow-hidden rounded-xl bg-muted sm:size-24">
			<FeedImage
				src={mediaUrl(album.cover)}
				alt={`${album.name} cover`}
				className="size-full object-cover"
			/>
		</div>
	)

	return (
		<GlassCard className="p-4 sm:p-5">
			<div className="flex items-center gap-4">
				{isSingles ? (
					cover
				) : (
					<Link
						href={`/videos/album/${album.id}/edit`}
						variant="unstyled">
						{cover}
					</Link>
				)}
				<div className="min-w-0 flex-1">
					<p className="text-xs text-muted-foreground">Video album</p>
					<h2 className="truncate text-xl font-bold">{album.name}</h2>
					<p className="text-xs text-muted-foreground">
						Released {album.released}
					</p>
				</div>
				{!isSingles && (
					<Link
						href={`/videos/album/${album.id}/edit`}
						variant="ghost"
						size="icon"
						aria-label={`Edit ${album.name}`}>
						<Pencil className="size-4" />
					</Link>
				)}
			</div>

			<div className="mt-4 divide-y divide-border/60 border-t border-border/60">
				{videos.length === 0 && (
					<p className="pt-4 text-sm text-muted-foreground">
						No videos in this album yet.
					</p>
				)}
				{videos.map((video) => (
					<StudioVideoRow
						key={video.id}
						video={video}
					/>
				))}
			</div>
		</GlassCard>
	)
}

function StudioVideoRow({ video }: { video: VideoDetail }) {
	return (
		<div className="flex gap-3 py-3">
			<Link
				href={`/videos/${video.id}/view`}
				variant="unstyled"
				className="relative aspect-video w-28 shrink-0 overflow-hidden rounded-lg bg-muted sm:w-36">
				<Film className="absolute inset-0 m-auto size-6 text-muted-foreground/40" />
				<FeedImage
					src={mediaUrl(video.thumbnail)}
					className="relative size-full object-cover"
				/>
			</Link>
			<div className="min-w-0 flex-1">
				<div className="flex items-start gap-2">
					<div className="min-w-0 flex-1">
						<Link
							href={`/videos/${video.id}/view`}
							variant="unstyled"
							className="line-clamp-1 font-semibold">
							{video.name}
						</Link>
						<p className="truncate text-xs text-muted-foreground">
							{[video.ft && `ft. ${video.ft}`, video.genre]
								.filter(Boolean)
								.join(" · ")}
						</p>
					</div>
					<Link
						href={`/videos/${video.id}/edit`}
						variant="outline"
						size="sm"
						className="h-7 px-3 text-xs">
						Edit
					</Link>
				</div>
				{video.description && (
					<p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
						{video.description}
					</p>
				)}
				<div className="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
					<span className="inline-flex items-center gap-1">
						<Download className="size-3" />
						{video.downloads}
					</span>
					<span className="font-medium text-primary">
						KES {(video.downloads * VIDEO_ARTIST_SHARE).toLocaleString()}
					</span>
					<span className="inline-flex items-center gap-1">
						<Heart className="size-3" />
						{video.likes}
					</span>
					<span>Released {video.released}</span>
					<span>Uploaded {video.createdAt}</span>
				</div>
			</div>
		</div>
	)
}
