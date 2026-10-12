import { useQueryClient } from "@tanstack/react-query"
import { Award, Clock, Search as SearchIcon, X } from "lucide-react"
import { useDeferredValue, useState, type ReactNode } from "react"
import { FeedImage } from "@/components/feed/feed-image"
import { VideoCard } from "@/components/feed/video-card"
import { AudioRow } from "@/components/profile/audio-row"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { useInitials } from "@/hooks/use-initials"
import { mediaUrl } from "@/lib/media"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { apiErrorMessage, useVideos } from "@/queries/feed"
import {
	socialActions,
	socialKeys,
	useAllArtists,
	useAllAudios,
	useAudioAlbums,
	useSearchHistory,
	useVideoAlbums,
} from "@/queries/social"
import type { Album } from "@/types/social"

export const isPublic = true

const RESULT_LIMIT = 10

/**
 * Search musicians, videos, audios and albums. Signed-in users get their
 * recent searches (saved when they open a result).
 */
export default function Search() {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const getInitials = useInitials()
	const [query, setQuery] = useState("")
	const deferredQuery = useDeferredValue(query.trim().toLowerCase())

	const history = useSearchHistory(Boolean(auth))
	const artists = useAllArtists()
	const videos = useVideos()
	const audios = useAllAudios()
	const videoAlbums = useVideoAlbums()
	const audioAlbums = useAudioAlbums()

	const matches = (value?: string | null) =>
		Boolean(value?.toLowerCase().includes(deferredQuery))
	const notMine = (username?: string | null) => username !== auth?.username

	const hasQuery = deferredQuery.length > 0

	const artistResults = hasQuery
		? (artists.data ?? []).filter(
				(artist) => matches(artist.name) || matches(artist.username)
			)
		: []
	const videoResults = hasQuery
		? (videos.data ?? []).filter(
				(video) =>
					notMine(video.username) && (matches(video.name) || matches(video.username))
			)
		: []
	const audioResults = hasQuery
		? (audios.data ?? []).filter(
				(audio) =>
					notMine(audio.username) && (matches(audio.name) || matches(audio.username))
			)
		: []
	const albumFilter = (album: Album) =>
		album.name !== "Singles" && notMine(album.username) && matches(album.name)
	const videoAlbumResults = hasQuery
		? (videoAlbums.data ?? []).filter(albumFilter)
		: []
	const audioAlbumResults = hasQuery
		? (audioAlbums.data ?? []).filter(albumFilter)
		: []

	const resultCount =
		artistResults.length +
		videoResults.length +
		audioResults.length +
		videoAlbumResults.length +
		audioAlbumResults.length

	const isLoading =
		artists.isPending || videos.isPending || audios.isPending

	// Remember what the user found (OLD saved the opened result's name)
	const onOpenResult = (keyword: string) => {
		if (!auth) {
			return
		}

		socialActions
			.saveSearch(keyword)
			.then(
				() =>
					void queryClient.invalidateQueries({
						queryKey: socialKeys.searchHistory,
					})
			)
			.catch(() => undefined)
	}

	const onDeleteSearch = (id: string) => {
		socialActions
			.deleteSearch(id)
			.then(
				() =>
					void queryClient.invalidateQueries({
						queryKey: socialKeys.searchHistory,
					})
			)
			.catch((error) => toast.error(apiErrorMessage(error)))
	}

	// Newest first, each keyword once
	const recent = [...(history.data ?? [])]
		.reverse()
		.filter(
			(item, index, list) =>
				list.findIndex((other) => other.keyword === item.keyword) === index
		)
		.slice(0, 15)

	return (
		<>
			<Head title="Search | Black Music" />
			<div className="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
				<label className="sticky top-2 z-30 flex h-12 items-center gap-2 rounded-full border border-border bg-background/80 px-4 shadow-sm backdrop-blur-xl focus-within:border-primary/60 focus-within:ring-2 focus-within:ring-primary/20">
					<SearchIcon className="size-5 text-muted-foreground" />
					<input
						autoFocus
						type="search"
						value={query}
						onChange={(event) => setQuery(event.target.value)}
						placeholder="Search songs, albums and artists"
						aria-label="Search"
						className="h-full flex-1 bg-transparent text-[15px] outline-none placeholder:text-muted-foreground [&::-webkit-search-cancel-button]:hidden"
					/>
					{query && (
						<Button
							variant="ghost"
							size="icon"
							className="size-8"
							aria-label="Clear search"
							onClick={() => setQuery("")}>
							<X className="size-4" />
						</Button>
					)}
				</label>

				{/* Recent searches */}
				{!hasQuery && auth && (
					<GlassCard className="p-4 sm:p-5">
						<h2 className="mb-2 font-semibold">Recent searches</h2>
						{history.isPending && <Skeleton className="h-8 w-full" />}
						{history.isSuccess && recent.length === 0 && (
							<p className="text-sm text-muted-foreground">
								Nothing yet. Searches you open show up here.
							</p>
						)}
						<ul>
							{recent.map((item) => (
								<li
									key={item.id}
									className="flex items-center gap-2 border-b border-border/40 last:border-none">
									<button
										type="button"
										onClick={() => setQuery(item.keyword)}
										className="flex min-w-0 flex-1 items-center gap-3 py-2.5 text-left text-sm hover:text-primary">
										<Clock className="size-4 shrink-0 text-muted-foreground" />
										<span className="truncate">{item.keyword}</span>
									</button>
									<Button
										variant="ghost"
										size="icon"
										className="size-8"
										aria-label={`Remove ${item.keyword}`}
										onClick={() => onDeleteSearch(item.id)}>
										<X className="size-4" />
									</Button>
								</li>
							))}
						</ul>
					</GlassCard>
				)}

				{!hasQuery && !auth && (
					<p className="text-center text-sm text-muted-foreground">
						Start typing to find musicians, videos and audios.
					</p>
				)}

				{hasQuery && isLoading && (
					<div className="space-y-3">
						<Skeleton className="h-20 w-full rounded-2xl" />
						<Skeleton className="h-20 w-full rounded-2xl" />
					</div>
				)}

				{hasQuery && !isLoading && resultCount === 0 && (
					<p className="text-center text-sm text-muted-foreground">
						No results for "{query.trim()}".
					</p>
				)}

				{artistResults.length > 0 && (
					<section>
						<h2 className="mb-3 font-semibold">Artists</h2>
						<div className="-mx-4 flex gap-4 overflow-x-auto px-4 pb-2 [scrollbar-width:none]">
							{artistResults.map((artist) => (
								<Link
									key={artist.id}
									href={`/profile/show/${artist.username}`}
									variant="unstyled"
									onClick={() => onOpenResult(artist.name)}
									className="w-20 shrink-0 text-center">
									<Avatar className="mx-auto size-16">
										<AvatarImage
											src={artist.avatar}
											alt={artist.name}
											className="object-cover"
										/>
										<AvatarFallback className="bg-primary/15 text-primary">
											{getInitials(artist.name)}
										</AvatarFallback>
									</Avatar>
									<p className="mt-1.5 truncate text-xs font-semibold">
										{artist.name}
									</p>
									<p className="flex items-center justify-center gap-0.5 truncate text-[11px] text-muted-foreground">
										{artist.decos > 0 && (
											<>
												<Award className="size-3 text-primary" />
												{artist.decos}
											</>
										)}
										{artist.decos === 0 && artist.username}
									</p>
								</Link>
							))}
						</div>
					</section>
				)}

				<div className="grid gap-6 md:grid-cols-2">
					{videoResults.length > 0 && (
						<ResultSection title="Videos">
							{videoResults.slice(0, RESULT_LIMIT).map((video) => (
								<div
									key={video.id}
									onClickCapture={() => onOpenResult(video.name)}>
									<VideoCard video={video} />
								</div>
							))}
						</ResultSection>
					)}

					{audioResults.length > 0 && (
						<ResultSection title="Audios">
							{audioResults.slice(0, RESULT_LIMIT).map((audio) => (
								<AudioRow
									key={audio.id}
									audio={audio}
									queue={audioResults}
									onPlay={() => onOpenResult(audio.name)}
								/>
							))}
						</ResultSection>
					)}

					{videoAlbumResults.length > 0 && (
						<ResultSection title="Video albums">
							{videoAlbumResults.map((album) => (
								<AlbumResult
									key={album.id}
									album={album}
									kind="Video"
									onOpen={() => onOpenResult(album.name)}
								/>
							))}
						</ResultSection>
					)}

					{audioAlbumResults.length > 0 && (
						<ResultSection title="Audio albums">
							{audioAlbumResults.map((album) => (
								<AlbumResult
									key={album.id}
									album={album}
									kind="Audio"
									onOpen={() => onOpenResult(album.name)}
								/>
							))}
						</ResultSection>
					)}
				</div>
			</div>
		</>
	)
}

function ResultSection({
	title,
	children,
}: {
	title: string
	children: ReactNode
}) {
	return (
		<GlassCard className="space-y-4 p-4 sm:p-5">
			<h2 className="font-semibold">{title}</h2>
			{children}
		</GlassCard>
	)
}

/** An album result, linking to its musician's profile. */
function AlbumResult({
	album,
	kind,
	onOpen,
}: {
	album: Album
	kind: "Video" | "Audio"
	onOpen: () => void
}) {
	return (
		<Link
			href={album.username ? `/profile/show/${album.username}` : "/search"}
			variant="unstyled"
			onClick={onOpen}
			className="flex items-center gap-3 rounded-2xl hover:bg-accent/40">
			<span className="size-16 shrink-0 overflow-hidden rounded-xl bg-muted">
				<FeedImage
					src={mediaUrl(album.cover)}
					className="size-full object-cover"
				/>
			</span>
			<span className="min-w-0">
				<span className="block text-xs text-muted-foreground">{kind} album</span>
				<span className="block truncate font-semibold">{album.name}</span>
				<span className="block truncate text-xs text-muted-foreground">
					{album.username} · {album.released}
				</span>
			</span>
		</Link>
	)
}
