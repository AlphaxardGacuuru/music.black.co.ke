import { Disc3, Heart, Music, Pencil, Plus, Upload, Video } from "lucide-react"
import { AudioRowSkeleton } from "@/components/audio/audio-row"
import { MusicianPrompt } from "@/components/audio/musician-prompt"
import { FeedImage } from "@/components/feed/feed-image"
import { GlassCard, GlassInner } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { mediaUrl } from "@/lib/media"
import { Head } from "@/lib/spa"
import {
	AUDIO_ARTIST_SHARE,
	useArtistAudioAlbums,
	useArtistAudios,
	useArtistBoughtAudios,
} from "@/queries/audio"
import type { AudioAlbum } from "@/types/audio"
import type { Audio } from "@/types/music"

/** The musician's audio studio (music.black's /audio): stats, albums and songs. */
export default function AudioStudio() {
	const { auth } = useApp()
	const username = auth?.username
	const isMusician = auth?.accountType === "musician"

	const albums = useArtistAudioAlbums(isMusician ? username : undefined)
	const audios = useArtistAudios(isMusician ? username : undefined)
	const sales = useArtistBoughtAudios(isMusician ? username : undefined)

	if (!isMusician) {
		return (
			<>
				<Head title="Audio studio | Black Music" />
				<MusicianPrompt kind="audio" />
			</>
		)
	}

	const albumList = albums.data ?? []
	const audioList = audios.data ?? []
	const downloads = sales.data?.length ?? 0
	// Every account starts with a "Singles" album, which isn't counted.
	const albumCount = albumList.filter((album) => album.name !== "Singles").length

	const stats = [
		{ label: "Audios", value: audioList.length },
		{ label: "Albums", value: albumCount },
		{ label: "Downloads", value: downloads },
		{ label: "Revenue", value: `KES ${downloads * AUDIO_ARTIST_SHARE}`, accent: true },
	]

	return (
		<div className="mx-auto w-full max-w-5xl space-y-6 py-2 md:py-4">
			<Head title="Audio studio | Black Music" />

			<div className="flex flex-wrap items-end justify-between gap-4">
				<div>
					<p className="text-xs font-medium uppercase tracking-widest text-primary">
						Studio
					</p>
					<h1 className="text-3xl font-bold tracking-tight">Audios</h1>
				</div>
				<div className="flex flex-wrap gap-2">
					<Link
						href="/videos"
						variant="outline"
						size="sm">
						<Video />
						Go to videos
					</Link>
					<Link
						href="/audios/album/create"
						variant="outline"
						size="sm">
						<Plus />
						Create album
					</Link>
					<Link
						href="/audios/create"
						variant="solid"
						size="sm">
						<Upload />
						Upload audio
					</Link>
				</div>
			</div>

			<div className="grid grid-cols-2 gap-3 md:grid-cols-4">
				{stats.map((stat) => (
					<GlassCard
						key={stat.label}
						className="p-4">
						<p className="text-xs text-muted-foreground">{stat.label}</p>
						{albums.isPending || audios.isPending || sales.isPending ? (
							<Skeleton className="mt-1 h-7 w-16" />
						) : (
							<p
								className={
									stat.accent
										? "text-2xl font-bold tabular-nums text-primary"
										: "text-2xl font-bold tabular-nums"
								}>
								{stat.value}
							</p>
						)}
					</GlassCard>
				))}
			</div>

			{albums.isPending && (
				<GlassCard className="space-y-3 p-5">
					<Skeleton className="h-20 w-48" />
					<AudioRowSkeleton />
					<AudioRowSkeleton />
				</GlassCard>
			)}

			{albums.isSuccess && albumList.length === 0 && (
				<GlassCard className="p-8 text-center text-sm text-muted-foreground">
					No albums yet. Create one, then upload your first song.
				</GlassCard>
			)}

			{albumList.map((album) => (
				<AlbumSection
					key={album.id}
					album={album}
					audios={audioList.filter((audio) => audio.audioAlbumId === album.id)}
				/>
			))}
		</div>
	)
}

function AlbumSection({ album, audios }: { album: AudioAlbum; audios: Audio[] }) {
	const isSingles = album.name === "Singles"

	const cover = (
		<span className="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-muted md:size-24">
			<Disc3 className="size-8 text-muted-foreground/60" />
			<FeedImage
				src={mediaUrl(album.cover)}
				alt={album.name}
				className="absolute inset-0 size-full object-cover"
			/>
		</span>
	)

	return (
		<GlassCard className="p-4 md:p-5">
			<div className="flex items-center gap-4">
				{isSingles ? (
					cover
				) : (
					<Link
						href={`/audios/album/${album.id}/edit`}
						variant="unstyled"
						aria-label={`Edit ${album.name}`}>
						{cover}
					</Link>
				)}
				<div className="min-w-0 flex-1">
					<p className="text-xs text-muted-foreground">Audio album</p>
					<h2 className="truncate text-xl font-bold">{album.name}</h2>
					<p className="text-xs text-muted-foreground">
						{album.released ? `Released ${album.released}` : album.createdAt}
					</p>
				</div>
				{!isSingles && (
					<Link
						href={`/audios/album/${album.id}/edit`}
						variant="ghost"
						size="icon"
						aria-label={`Edit ${album.name}`}>
						<Pencil />
					</Link>
				)}
			</div>

			<div className="mt-4 space-y-2">
				{audios.length === 0 && (
					<p className="text-sm text-muted-foreground">No songs in this album yet.</p>
				)}
				{audios.map((audio) => (
					<GlassInner
						key={audio.id}
						className="flex items-center gap-3 p-2.5">
						<Link
							href={`/audios/${audio.id}/view`}
							variant="unstyled"
							className="relative flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-muted">
							<Music className="size-5 text-muted-foreground" />
							<FeedImage
								src={mediaUrl(audio.thumbnail)}
								className="absolute inset-0 size-full object-cover"
							/>
						</Link>
						<div className="min-w-0 flex-1">
							<p className="truncate text-sm font-semibold">
								{audio.name}
								{audio.ft && (
									<span className="font-normal text-muted-foreground"> ft. {audio.ft}</span>
								)}
							</p>
							<p className="truncate text-xs text-muted-foreground">
								{[audio.genre, audio.released && `Released ${audio.released}`, `Uploaded ${audio.createdAt}`]
									.filter(Boolean)
									.join(" · ")}
							</p>
							{audio.description && (
								<p className="hidden truncate text-xs text-muted-foreground sm:block">
									{audio.description}
								</p>
							)}
							<p className="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs">
								<span>
									<span className="tabular-nums">{audio.downloads}</span>{" "}
									<span className="text-muted-foreground">downloads</span>
								</span>
								<span className="font-medium text-primary tabular-nums">
									KES {audio.downloads * AUDIO_ARTIST_SHARE}
								</span>
								<span className="inline-flex items-center gap-1 text-muted-foreground">
									<Heart className="size-3" />
									<span className="tabular-nums">{audio.likes}</span>
								</span>
							</p>
						</div>
						<Link
							href={`/audios/${audio.id}/edit`}
							variant="outline"
							size="sm">
							Edit
						</Link>
					</GlassInner>
				))}
			</div>
		</GlassCard>
	)
}
