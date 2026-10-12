import { Mic2, Music2, Play, Video as VideoIcon } from "lucide-react"
import { useState } from "react"
import { DownloadButton } from "@/components/cart/download-button"
import { SongRow } from "@/components/cart/song-row"
import { KaraokeTile } from "@/components/karaoke/karaoke-tile"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { Head } from "@/lib/spa"
import { cn } from "@/lib/utils"
import {
	boughtToAudio,
	useBoughtAudios,
	useBoughtVideos,
} from "@/queries/commerce"
import { useSavedKaraokes } from "@/queries/karaoke"

type Tab = "videos" | "audios" | "karaokes"

const TABS: { value: Tab; label: string; icon: typeof VideoIcon }[] = [
	{ value: "videos", label: "Videos", icon: VideoIcon },
	{ value: "audios", label: "Audios", icon: Music2 },
	{ value: "karaokes", label: "Karaokes", icon: Mic2 },
]

const PLACEHOLDERS = [1, 2, 3, 4]

/** Library: songs the user bought (play and download) and saved karaokes. */
export default function Library() {
	const [tab, setTab] = useState<Tab>("videos")
	const player = useAudioPlayer()

	const videos = useBoughtVideos()
	const audios = useBoughtAudios()
	const karaokes = useSavedKaraokes()

	const audioQueue = (audios.data ?? []).map(boughtToAudio)

	const counts: Record<Tab, number | undefined> = {
		videos: videos.data?.length,
		audios: audios.data?.length,
		karaokes: karaokes.data?.length,
	}

	return (
		<>
			<Head title="Library | Black Music" />

			<div className="mx-auto w-full max-w-4xl px-4 py-6 lg:px-6">
				<header className="mb-5">
					<h1 className="text-2xl font-semibold tracking-tight">Library</h1>
					<p className="text-sm text-muted-foreground">
						Everything you've bought, plus the karaokes you saved.
					</p>
				</header>

				<div
					role="tablist"
					aria-label="Library sections"
					className="mb-5 grid grid-cols-3 gap-1 rounded-full border border-border/60 bg-muted/40 p-1">
					{TABS.map((item) => (
						<button
							key={item.value}
							type="button"
							role="tab"
							aria-selected={tab === item.value}
							onClick={() => setTab(item.value)}
							className={cn(
								"flex items-center justify-center gap-1.5 rounded-full px-2 py-2 text-sm font-medium transition-colors",
								tab === item.value
									? "bg-primary text-primary-foreground shadow-sm"
									: "text-muted-foreground hover:text-foreground"
							)}>
							<item.icon className="size-4 shrink-0" />
							<span>{item.label}</span>
							{counts[item.value] !== undefined && (
								<span className="hidden text-xs opacity-75 sm:inline">
									{counts[item.value]}
								</span>
							)}
						</button>
					))}
				</div>

				{tab === "videos" && (
					<GlassCard className="space-y-4 p-5">
						{videos.isPending && <RowSkeletons video />}
						{videos.data?.map((video) => (
							<SongRow
								key={video.id}
								kind="video"
								{...video}>
								<DownloadButton
									kind="video"
									id={video.id}
									label={`Download ${video.name}`}
								/>
							</SongRow>
						))}
						{videos.data?.length === 0 && (
							<Empty
								text="You haven't bought any videos yet."
								href="/video/charts"
								cta="Browse video charts"
							/>
						)}
					</GlassCard>
				)}

				{tab === "audios" && (
					<GlassCard className="space-y-4 p-5">
						{audioQueue.length > 1 && (
							<Button
								size="sm"
								className="gap-1.5"
								onClick={() => player.play(audioQueue[0], audioQueue)}>
								<Play className="size-4" />
								Play all
							</Button>
						)}
						{audios.isPending && <RowSkeletons />}
						{audioQueue.map((audio) => (
							<SongRow
								key={audio.id}
								kind="audio"
								{...audio}
								isPlaying={player.current?.id === audio.id && player.isPlaying}
								onPlay={() =>
									player.current?.id === audio.id
										? player.toggle()
										: player.play(audio, audioQueue)
								}>
								<DownloadButton
									kind="audio"
									id={audio.id}
									label={`Download ${audio.name}`}
								/>
							</SongRow>
						))}
						{audios.data?.length === 0 && (
							<Empty
								text="You haven't bought any audios yet."
								href="/audio/charts"
								cta="Browse audio charts"
							/>
						)}
					</GlassCard>
				)}

				{tab === "karaokes" && (
					<GlassCard className="p-5">
						{karaokes.isPending && (
							<div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
								{PLACEHOLDERS.map((key) => (
									<Skeleton
										key={key}
										className="aspect-[9/16] rounded-2xl"
									/>
								))}
							</div>
						)}
						{karaokes.data && karaokes.data.length > 0 && (
							<div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
								{karaokes.data.map((karaoke) => (
									<KaraokeTile
										key={karaoke.id}
										karaoke={karaoke}
									/>
								))}
							</div>
						)}
						{karaokes.data?.length === 0 && (
							<Empty
								text="You haven't saved any karaokes yet."
								href="/karaoke/charts"
								cta="Browse karaokes"
							/>
						)}
					</GlassCard>
				)}
			</div>
		</>
	)
}

function RowSkeletons({ video = false }: { video?: boolean }) {
	return PLACEHOLDERS.map((key) => (
		<div
			key={key}
			className="flex items-center gap-3">
			<Skeleton
				className={cn("rounded-xl", video ? "aspect-video w-28" : "size-14")}
			/>
			<Skeleton className="h-4 flex-1" />
		</div>
	))
}

function Empty({ text, href, cta }: { text: string; href: string; cta: string }) {
	return (
		<div className="flex flex-col items-center gap-3 py-6 text-center">
			<p className="text-sm text-muted-foreground">{text}</p>
			<Link
				href={href}
				variant="outline"
				size="sm">
				{cta}
			</Link>
		</div>
	)
}
