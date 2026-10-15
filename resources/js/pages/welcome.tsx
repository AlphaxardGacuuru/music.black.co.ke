import {
	ArrowRight,
	Clapperboard,
	Disc3,
	Headphones,
	MessageSquare,
	MicVocal,
	Newspaper,
	Pause,
	SkipBack,
	SkipForward,
	TrendingUp,
	Upload,
	Wallet,
} from "lucide-react"
import AppLogo from "@/components/app-logo"
import { BackdropLines } from "@/components/backdrop-lines"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Head } from "@/lib/spa"
import { useApp } from "@/contexts/AppContext"

const features = [
	{
		icon: Clapperboard,
		title: "Music videos",
		description: "Watch previews for free and buy the full video to keep.",
		price: "KES 20",
	},
	{
		icon: Headphones,
		title: "Audio tracks",
		description: "Stream songs and albums, then buy the ones you love.",
		price: "KES 10",
	},
	{
		icon: MicVocal,
		title: "Karaoke",
		description: "Sing along to your favourite tracks and share your take.",
	},
	{
		icon: Newspaper,
		title: "Feed and stories",
		description:
			"Follow artists for posts, polls, photos and stories from the studio.",
	},
	{
		icon: TrendingUp,
		title: "Charts",
		description: "See what is trending, newly released and selling most.",
	},
	{
		icon: MessageSquare,
		title: "Chat",
		description: "Talk directly with the artists and fans you follow.",
	},
]

const forMusicians = [
	{
		icon: Upload,
		title: "Upload your music",
		description: "Post your videos and audio, single tracks or full albums.",
	},
	{
		icon: Newspaper,
		title: "Build your fanbase",
		description:
			"Share posts, polls and stories so your fans hear from you first.",
	},
	{
		icon: Wallet,
		title: "Earn from every sale",
		description:
			"Each time a fan buys your song or video, you get paid straight to M-Pesa.",
	},
]

export default function Welcome() {
	const { auth } = useApp()

	const primaryCta = auth
		? { href: "/", label: "Go to your feed" }
		: { href: "/login", label: "Join Black Music" }

	return (
		<div className="relative isolate min-h-screen overflow-hidden bg-background text-foreground">
			<BackdropLines />

			{/* A single stage light from above, rather than scattered glows. */}
			<div className="pointer-events-none absolute inset-x-0 top-0 z-0 flex justify-center overflow-hidden">
				<div className="h-144 w-240 -translate-y-1/2 rounded-full bg-primary/30 blur-3xl dark:bg-primary/22" />
			</div>

			<Head title="Black Music. Stream, buy and support the artists you love" />

			<header className="relative z-10 mx-auto flex max-w-6xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
				<Link
					href="/welcome"
					variant="unstyled"
					size="none">
					<AppLogo className="h-16 w-auto" />
				</Link>
				<div className="flex items-center gap-2">
					<Link
						href="/get-app"
						variant="ghost"
						className="hidden sm:inline-flex">
						Get the app
					</Link>
					{auth ? (
						<Link
							href="/"
							variant="solid">
							Home
						</Link>
					) : (
						<Link
							href="/login"
							variant="solid">
							Sign in
						</Link>
					)}
				</div>
			</header>

			<section className="relative z-10 mx-auto max-w-4xl px-4 pb-12 pt-12 text-center sm:px-6 lg:pt-20">
				<p className="text-xs font-semibold uppercase tracking-[0.3em] text-primary">
					The music store and social network for artists
				</p>

				<h1 className="mt-6 text-5xl font-extrabold tracking-tight text-balance sm:text-6xl lg:text-7xl">
					Stream it. Buy it.{" "}
					<span className="text-primary">Back the artist.</span>
				</h1>

				<p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-muted-foreground sm:text-xl">
					Black Music is where musicians post their videos and audio and earn
					from them, and where fans discover new sounds, sing karaoke and keep
					up with the artists they love.
				</p>

				<div className="mt-8 flex flex-wrap justify-center gap-3">
					<Link
						href={primaryCta.href}
						variant="solid"
						size="lg"
						className="rounded-full px-8 shadow-lg shadow-primary/20">
						{primaryCta.label}
					</Link>
					<Link
						href="/get-app"
						variant="outline"
						size="lg"
						className="gap-2 rounded-full border-border/70 bg-background/70 px-8 backdrop-blur-sm">
						<Disc3 className="size-4" />
						Get the app
					</Link>
				</div>

				<div className="mt-6 flex flex-wrap justify-center gap-2 text-sm">
					<span className="rounded-full border border-border/70 bg-background/70 px-3 py-1.5 backdrop-blur-sm">
						Songs from{" "}
						<span className="font-semibold text-primary tabular-nums">
							KES 10
						</span>
					</span>
					<span className="rounded-full border border-border/70 bg-background/70 px-3 py-1.5 backdrop-blur-sm">
						Videos from{" "}
						<span className="font-semibold text-primary tabular-nums">
							KES 20
						</span>
					</span>
				</div>
			</section>

			<section className="relative z-10 mx-auto max-w-xl px-4 pb-20 sm:px-6">
				<GlassCard className="flex items-center gap-4 rounded-3xl p-4 sm:gap-5 sm:p-5">
					<div className="flex size-16 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/30 sm:size-20">
						<Disc3 className="size-10 animate-[spin_4s_linear_infinite] motion-reduce:animate-none sm:size-12" />
					</div>
					<div className="min-w-0 flex-1 text-left">
						<p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
							Now playing
						</p>
						<p className="truncate font-semibold">Your next favourite song</p>
						<div className="mt-3 h-1 overflow-hidden rounded-full bg-primary/15">
							<div className="h-full w-2/5 rounded-full bg-primary" />
						</div>
						<div className="mt-3 flex items-center justify-between gap-3">
							<div className="flex items-center gap-3 text-muted-foreground">
								<SkipBack className="size-4" />
								<Pause className="size-5 text-foreground" />
								<SkipForward className="size-4" />
							</div>
							<span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary tabular-nums">
								Buy · KES 10
							</span>
						</div>
					</div>
				</GlassCard>
			</section>

			<section className="relative z-10 mx-auto max-w-4xl px-4 pb-24 sm:px-6 lg:px-8">
				<div className="mb-6 flex items-end justify-between gap-4 border-b border-border/70 pb-4">
					<div>
						<p className="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
							For fans
						</p>
						<h2 className="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">
							The tracklist
						</h2>
					</div>
					<p className="text-sm text-muted-foreground tabular-nums">
						{features.length} tracks
					</p>
				</div>

				<ol className="divide-y divide-border/50">
					{features.map(({ icon: Icon, title, description, price }, index) => (
						<li
							key={title}
							className="group flex items-center gap-4 rounded-xl px-2 py-4 transition-colors hover:bg-primary/5 sm:gap-6 sm:px-4">
							<span className="w-6 shrink-0 text-sm font-semibold text-muted-foreground tabular-nums group-hover:text-primary">
								{String(index + 1).padStart(2, "0")}
							</span>
							<Icon className="size-5 shrink-0 text-primary" />
							<div className="min-w-0 flex-1">
								<p className="font-medium">{title}</p>
								<p className="mt-0.5 text-sm text-muted-foreground">
									{description}
								</p>
							</div>
							{price && (
								<span className="shrink-0 text-sm font-semibold tabular-nums text-primary">
									{price}
								</span>
							)}
						</li>
					))}
				</ol>
			</section>

			<section className="relative z-10 border-y border-border/60 bg-background/60 backdrop-blur-sm">
				<div className="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
					<div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
						<div>
							<p className="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
								For musicians
							</p>
							<h2 className="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">
								Get paid for your music
							</h2>
						</div>
						<Link
							href={auth ? "/" : "/login"}
							variant="solid"
							size="lg"
							className="gap-2 self-start rounded-full px-6 sm:self-auto">
							Start uploading
							<ArrowRight className="size-4" />
						</Link>
					</div>

					<div className="mt-10 grid gap-8 md:grid-cols-3">
						{forMusicians.map(({ icon: Icon, title, description }, index) => (
							<div
								key={title}
								className="relative">
								<div className="flex items-center gap-3">
									<span className="text-4xl font-extrabold tracking-tight text-primary/30 tabular-nums">
										{String(index + 1).padStart(2, "0")}
									</span>
									<Icon className="size-5 text-primary" />
								</div>
								<p className="mt-3 text-lg font-semibold">{title}</p>
								<p className="mt-1 text-sm text-muted-foreground">
									{description}
								</p>
							</div>
						))}
					</div>
				</div>
			</section>
		</div>
	)
}
