import { useEffect, useMemo, useRef, useState, type ReactNode } from "react"
import { AudioRow, AudioRowSkeleton } from "@/components/audio/audio-row"
import { UserAvatar } from "@/components/feed/user-avatar"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { Head } from "@/lib/spa"
import { cn } from "@/lib/utils"
import { GENRES, useAudioChart } from "@/queries/audio"
import type { AudioChartName } from "@/types/audio"

export const isPublic = true

const CHARTS: { name: AudioChartName; label: string }[] = [
	{ name: "newly-released", label: "Newly Released" },
	{ name: "trending", label: "Trending" },
	{ name: "top-downloaded", label: "Top Downloaded" },
	{ name: "top-liked", label: "Top Liked" },
]

const SECTIONS = [
	{ href: "/karaoke/charts", label: "Karaoke" },
	{ href: "/videos/charts", label: "Videos" },
	{ href: "/audios/charts", label: "Audios" },
]

const PAGE = 8
const PLACEHOLDERS = [1, 2, 3, 4, 5, 6]

function Chip({
	active,
	onClick,
	children,
}: {
	active: boolean
	onClick: () => void
	children: ReactNode
}) {
	return (
		<button
			type="button"
			onClick={onClick}
			aria-pressed={active}
			className={cn(
				"shrink-0 cursor-pointer rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors",
				active
					? "border-primary bg-primary text-primary-foreground"
					: "border-border/70 text-muted-foreground hover:border-primary/60 hover:text-foreground"
			)}>
			{children}
		</button>
	)
}

/** Audio charts (music.black's /audio/charts): charts, genres, artists and songs. */
export default function AudioCharts() {
	const [chart, setChart] = useState<AudioChartName>("newly-released")
	const [genre, setGenre] = useState("All")
	const [limit, setLimit] = useState(PAGE)
	const sentinel = useRef<HTMLDivElement>(null)

	const query = useAudioChart(chart)

	const artists = (query.data?.artists ?? []).filter(
		(artist) => artist.username !== "@blackmusic"
	)

	const audios = useMemo(
		() =>
			(query.data?.audios ?? [])
				.filter((audio) => audio.username !== "@blackmusic")
				.filter((audio) => genre === "All" || audio.genre === genre),
		[query.data, genre]
	)

	const visible = audios.slice(0, limit)
	const hasMore = audios.length > limit

	// Load more songs as the list's end scrolls into view.
	useEffect(() => {
		const el = sentinel.current

		if (!el || !hasMore) {
			return
		}

		const observer = new IntersectionObserver((entries) => {
			if (entries.some((entry) => entry.isIntersecting)) {
				setLimit((value) => value + PAGE)
			}
		})
		observer.observe(el)

		return () => observer.disconnect()
	}, [hasMore, visible.length])

	const onChart = (name: AudioChartName) => {
		setChart(name)
		setLimit(PAGE)
	}

	const onGenre = (name: string) => {
		setGenre(name)
		setLimit(PAGE)
	}

	return (
		<div className="mx-auto w-full max-w-4xl space-y-6 py-2 md:py-4">
			<Head title="Audio charts | Black Music" />

			<nav className="flex items-baseline gap-5">
				{SECTIONS.map((section) => (
					<Link
						key={section.href}
						href={section.href}
						variant="unstyled"
						aria-current={section.label === "Audios" ? "page" : undefined}
						className={cn(
							"text-2xl font-bold tracking-tight transition-colors md:text-3xl",
							section.label === "Audios"
								? "text-foreground"
								: "text-muted-foreground/60 hover:text-muted-foreground"
						)}>
						{section.label}
					</Link>
				))}
			</nav>

			<div className="space-y-3">
				<div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]">
					{CHARTS.map((item) => (
						<Chip
							key={item.name}
							active={chart === item.name}
							onClick={() => onChart(item.name)}>
							{item.label}
						</Chip>
					))}
				</div>
				<div className="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-1 [scrollbar-width:none]">
					{["All", ...GENRES].map((item) => (
						<button
							key={item}
							type="button"
							onClick={() => onGenre(item)}
							aria-pressed={genre === item}
							className={cn(
								"shrink-0 cursor-pointer rounded-full px-3 py-1 text-xs font-medium transition-colors",
								genre === item
									? "bg-primary/15 text-primary"
									: "text-muted-foreground hover:text-foreground"
							)}>
							{item}
						</button>
					))}
				</div>
			</div>

			<section>
				<h2 className="mb-3 text-lg font-semibold">Artists</h2>
				<div className="-mx-4 flex gap-4 overflow-x-auto px-4 pb-2 [scrollbar-width:none]">
					{query.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="flex w-16 shrink-0 flex-col items-center gap-2">
								<Skeleton className="size-16 rounded-full" />
								<Skeleton className="h-3 w-12" />
							</div>
						))}
					{artists.map((artist) => (
						<div
							key={artist.id}
							className="flex w-16 shrink-0 flex-col items-center gap-1.5 text-center">
							<UserAvatar
								name={artist.name}
								username={artist.username}
								avatar={artist.avatar}
								className="size-16 text-base"
							/>
							<Link
								href={`/profile/${artist.username}/view`}
								variant="unstyled"
								className="w-full truncate text-xs text-muted-foreground hover:text-foreground">
								{artist.username}
							</Link>
						</div>
					))}
					{query.isSuccess && artists.length === 0 && (
						<p className="text-sm text-muted-foreground">
							No artists in this chart yet.
						</p>
					)}
				</div>
			</section>

			<GlassCard className="p-4 md:p-5">
				<h2 className="mb-2 text-lg font-semibold">Songs</h2>
				{query.isPending &&
					PLACEHOLDERS.map((key) => <AudioRowSkeleton key={key} />)}
				{query.isError && (
					<p className="py-4 text-sm text-muted-foreground">
						Couldn't load this chart.
					</p>
				)}
				<div className="divide-y divide-border/50">
					{visible.map((audio, index) => (
						<AudioRow
							key={audio.id}
							audio={audio}
							queue={audios}
							rank={index + 1}
						/>
					))}
				</div>
				{query.isSuccess && audios.length === 0 && (
					<p className="py-4 text-sm text-muted-foreground">
						{genre === "All"
							? "No songs in this chart yet."
							: `No ${genre} songs in this chart yet.`}
					</p>
				)}
				{hasMore && (
					<div
						ref={sentinel}
						className="py-2">
						<AudioRowSkeleton />
					</div>
				)}
			</GlassCard>
		</div>
	)
}
