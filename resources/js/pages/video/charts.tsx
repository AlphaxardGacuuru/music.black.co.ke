import { Award } from "lucide-react"
import { useEffect, useRef, useState } from "react"
import { UserAvatar } from "@/components/feed/user-avatar"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { VideoTile } from "@/components/video/video-tile"
import { Head } from "@/lib/spa"
import { cn } from "@/lib/utils"
import { useVideoChart } from "@/queries/video"
import { VIDEO_CHARTS, VIDEO_GENRES, type VideoChartSlug } from "@/types/video"

export const isPublic = true

const PAGE_SIZE = 12
const PLACEHOLDERS = [1, 2, 3, 4, 5, 6, 7, 8]

const CHART_AREAS = [
	{ label: "Karaoke", href: "/karaoke/charts" },
	{ label: "Videos", href: "/video/charts" },
	{ label: "Audios", href: "/audio/charts" },
]

function Chip({
	active,
	onClick,
	children,
}: {
	active: boolean
	onClick: () => void
	children: React.ReactNode
}) {
	return (
		<button
			type="button"
			onClick={onClick}
			aria-pressed={active}
			className={cn(
				"shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors",
				active
					? "border-primary bg-primary text-primary-foreground"
					: "border-border/60 text-muted-foreground hover:text-foreground"
			)}>
			{children}
		</button>
	)
}

export default function VideoCharts() {
	const [chart, setChart] = useState<VideoChartSlug>("newly-released")
	const [genre, setGenre] = useState("All")
	const [visible, setVisible] = useState(PAGE_SIZE)
	const sentinel = useRef<HTMLDivElement>(null)

	const data = useVideoChart(chart)

	const artists = (data.data?.artists ?? []).filter(
		(artist) => artist.username !== "@blackmusic"
	)
	const videos = (data.data?.videos ?? [])
		.filter((video) => video.username !== "@blackmusic")
		.filter((video) => genre === "All" || video.genre === genre)

	const hasMore = visible < videos.length

	// Show more videos as the end of the list scrolls into view.
	useEffect(() => {
		const element = sentinel.current

		if (!element || !hasMore) {
			return
		}

		const observer = new IntersectionObserver((entries) => {
			if (entries[0]?.isIntersecting) {
				setVisible((count) => count + PAGE_SIZE)
			}
		})
		observer.observe(element)

		return () => observer.disconnect()
	}, [hasMore, visible])

	const pickChart = (slug: VideoChartSlug) => {
		setChart(slug)
		setVisible(PAGE_SIZE)
	}

	const pickGenre = (value: string) => {
		setGenre(value)
		setVisible(PAGE_SIZE)
	}

	return (
		<div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 lg:px-6">
			<Head title="Video charts | Black Music" />

			<header className="space-y-4">
				<nav className="flex items-baseline gap-5">
					{CHART_AREAS.map((area) => (
						<Link
							key={area.href}
							href={area.href}
							variant="unstyled"
							aria-current={area.label === "Videos" ? "page" : undefined}
							className={cn(
								"text-2xl font-bold transition-colors sm:text-3xl",
								area.label === "Videos"
									? "text-foreground"
									: "text-muted-foreground/60 hover:text-muted-foreground"
							)}>
							{area.label}
						</Link>
					))}
				</nav>

				<div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] lg:mx-0 lg:px-0">
					{VIDEO_CHARTS.map((item) => (
						<Chip
							key={item.slug}
							active={chart === item.slug}
							onClick={() => pickChart(item.slug)}>
							{item.label}
						</Chip>
					))}
				</div>

				<div className="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-1 [scrollbar-width:none] lg:mx-0 lg:px-0">
					{["All", ...VIDEO_GENRES].map((item) => (
						<button
							key={item}
							type="button"
							onClick={() => pickGenre(item)}
							aria-pressed={genre === item}
							className={cn(
								"shrink-0 rounded-full px-3 py-1 text-xs font-medium transition-colors",
								genre === item
									? "bg-primary/15 text-primary"
									: "text-muted-foreground hover:text-foreground"
							)}>
							{item}
						</button>
					))}
				</div>
			</header>

			<section>
				<h2 className="mb-3 text-lg font-semibold">Artists</h2>
				<div className="-mx-4 flex gap-4 overflow-x-auto px-4 pb-2 [scrollbar-width:none] lg:mx-0 lg:px-0">
					{data.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="flex w-20 shrink-0 flex-col items-center gap-2">
								<Skeleton className="size-16 rounded-full" />
								<Skeleton className="h-3 w-14" />
							</div>
						))}
					{artists.map((artist) => (
						<div
							key={artist.id}
							className="flex w-20 shrink-0 flex-col items-center text-center">
							<UserAvatar
								name={artist.name}
								username={artist.username}
								avatar={artist.avatar}
								className="size-16"
							/>
							<Link
								href={`/profile/show/${artist.username}`}
								variant="unstyled"
								className="mt-1.5 w-full truncate text-xs font-medium">
								{artist.username}
							</Link>
							{artist.decos > 0 && (
								<span className="inline-flex items-center gap-0.5 text-[11px] text-primary">
									<Award className="size-3" />
									{artist.decos}
								</span>
							)}
						</div>
					))}
					{!data.isPending && artists.length === 0 && (
						<p className="text-sm text-muted-foreground">
							No artists on this chart yet.
						</p>
					)}
				</div>
			</section>

			<section>
				<h2 className="mb-3 text-lg font-semibold">Songs</h2>
				<div className="grid gap-x-4 gap-y-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
					{data.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="space-y-2.5">
								<Skeleton className="aspect-video w-full rounded-2xl" />
								<div className="flex gap-2.5">
									<Skeleton className="size-8 rounded-full" />
									<div className="flex-1 space-y-2">
										<Skeleton className="h-4 w-full" />
										<Skeleton className="h-3 w-1/2" />
									</div>
								</div>
							</div>
						))}
					{videos.slice(0, visible).map((video) => (
						<VideoTile
							key={`${chart}-${video.id}`}
							video={video}
						/>
					))}
				</div>
				{data.isError && (
					<p className="text-sm text-muted-foreground">
						Couldn't load this chart. Try again later.
					</p>
				)}
				{!data.isPending && !data.isError && videos.length === 0 && (
					<p className="text-sm text-muted-foreground">
						No videos here yet{genre !== "All" && ` for ${genre}`}.
					</p>
				)}
				<div
					ref={sentinel}
					className="h-px"
				/>
			</section>
		</div>
	)
}
