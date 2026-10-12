import { useEcho } from "@laravel/echo-react"
import { useQueryClient } from "@tanstack/react-query"
import { ArrowUp, Award, Camera, MessagesSquare, PenLine } from "lucide-react"
import { useState } from "react"
import { MusicianRow } from "@/components/feed/musician-row"
import { PostCard } from "@/components/feed/post-card"
import { StoryCard } from "@/components/feed/story-card"
import { UserAvatar } from "@/components/feed/user-avatar"
import { VideoCard } from "@/components/feed/video-card"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import {
	feedKeys,
	useArtists,
	usePosts,
	useStories,
	useVideos,
} from "@/queries/feed"

const PLACEHOLDERS = [1, 2, 3, 4, 5]

/**
 * The signed-in home page, ported from music.black's index page: profile
 * summary and musicians to follow on the left, stories and the posts feed in
 * the middle, and songs to watch on the right.
 */
export function HomeFeed() {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const [hasNewPosts, setHasNewPosts] = useState(false)
	const [deletedIds, setDeletedIds] = useState<string[]>([])

	const posts = usePosts()
	const stories = useStories()
	const videos = useVideos()
	const artists = useArtists()

	useEcho("post-created", "PostedEvent", () => setHasNewPosts(true))

	const isMusician = auth?.accountType === "musician"

	const onShowNewPosts = () => {
		setHasNewPosts(false)
		void queryClient.invalidateQueries({ queryKey: feedKeys.posts })
		window.scrollTo({ top: 0, behavior: "smooth" })
	}

	const allPosts = (
		posts.data?.pages.flatMap((page) => page.data) ?? []
	).filter((post) => !deletedIds.includes(post.id))

	const videosToWatch = (videos.data ?? [])
		.filter((video) => !video.hasBoughtVideo)
		.slice(0, 10)

	return (
		<div className="mx-auto grid w-full max-w-7xl gap-6 px-4 py-6 lg:grid-cols-[17rem_minmax(0,1fr)_19rem] lg:px-6">
			{hasNewPosts && (
				<Button
					onClick={onShowNewPosts}
					className="fixed left-1/2 top-4 z-50 -translate-x-1/2 gap-2 shadow-lg shadow-primary/30">
					<ArrowUp className="size-4" />
					New posts
				</Button>
			)}

			{/* Profile and musicians to follow */}
			<aside className="hidden space-y-6 lg:block">
				{auth && (
					<GlassCard className="p-5">
						<div className="flex items-center gap-3">
							<UserAvatar
								name={auth.name}
								username={String(auth.username ?? "")}
								avatar={auth.avatar}
								className="size-14"
							/>
							<div className="min-w-0">
								<p className="truncate font-semibold">{auth.name}</p>
								<p className="truncate text-sm text-muted-foreground">
									{String(auth.username ?? "")}
								</p>
								<p className="mt-0.5 inline-flex items-center gap-1 text-xs font-medium text-primary">
									<Award className="size-3.5" />
									{Number(auth.decos ?? 0)}
								</p>
							</div>
						</div>
						<div className="mt-4 grid grid-cols-2 gap-3 border-t border-border/60 pt-4 text-center">
							<div>
								<p className="text-lg font-bold tabular-nums">
									{Number(auth.posts ?? 0)}
								</p>
								<p className="text-xs text-muted-foreground">Posts</p>
							</div>
							<div>
								<p className="text-lg font-bold tabular-nums">
									{Number(auth.fans ?? 0)}
								</p>
								<p className="text-xs text-muted-foreground">Fans</p>
							</div>
						</div>
					</GlassCard>
				)}

				<GlassCard className="p-5">
					<h2 className="mb-2 font-semibold">Musicians to follow</h2>
					{artists.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="flex items-center gap-3 py-2">
								<Skeleton className="size-9 rounded-full" />
								<Skeleton className="h-4 flex-1" />
							</div>
						))}
					{artists.data?.map((artist) => (
						<MusicianRow
							key={artist.id}
							artist={artist}
						/>
					))}
					{artists.data?.length === 0 && (
						<p className="text-sm text-muted-foreground">
							No musicians to suggest yet.
						</p>
					)}
				</GlassCard>
			</aside>

			{/* Stories and posts */}
			<main className="min-w-0 space-y-6">
				<section>
					<h2 className="mb-3 font-semibold">Stories</h2>
					<div className="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 [scrollbar-width:none] lg:mx-0 lg:px-0">
						{stories.isPending &&
							PLACEHOLDERS.map((key) => (
								<Skeleton
									key={key}
									className="aspect-[9/16] w-28 shrink-0 rounded-2xl sm:w-32"
								/>
							))}
						{stories.data?.map((story) => (
							<StoryCard
								key={story.id}
								story={story}
							/>
						))}
						{stories.data?.length === 0 && (
							<p className="text-sm text-muted-foreground">No stories yet.</p>
						)}
					</div>
				</section>

				<section className="space-y-4">
					{posts.isPending &&
						PLACEHOLDERS.slice(0, 3).map((key) => (
							<GlassCard
								key={key}
								className="space-y-3 p-5">
								<div className="flex items-center gap-3">
									<Skeleton className="size-10 rounded-full" />
									<Skeleton className="h-4 w-40" />
								</div>
								<Skeleton className="h-4 w-full" />
								<Skeleton className="h-4 w-2/3" />
							</GlassCard>
						))}

					{allPosts.map((post) => (
						<PostCard
							key={post.id}
							post={post}
							onDeleted={(id) => setDeletedIds((ids) => [...ids, id])}
						/>
					))}

					{posts.isSuccess && allPosts.length === 0 && (
						<GlassCard className="p-8 text-center text-sm text-muted-foreground">
							No posts yet. Follow musicians to fill your feed.
						</GlassCard>
					)}

					{posts.hasNextPage && (
						<Button
							variant="outline"
							className="w-full"
							disabled={posts.isFetchingNextPage}
							onClick={() => void posts.fetchNextPage()}>
							{posts.isFetchingNextPage ? "Loading..." : "Load more"}
						</Button>
					)}
				</section>
			</main>

			{/* Songs to watch */}
			<aside className="hidden lg:block">
				<GlassCard className="space-y-4 p-5">
					<h2 className="font-semibold">Songs to watch</h2>
					{videos.isPending &&
						PLACEHOLDERS.map((key) => (
							<div
								key={key}
								className="flex gap-3">
								<Skeleton className="aspect-video w-36 rounded-xl" />
								<Skeleton className="h-4 flex-1" />
							</div>
						))}
					{videosToWatch.map((video) => (
						<VideoCard
							key={video.id}
							video={video}
						/>
					))}
					{videos.isSuccess && videosToWatch.length === 0 && (
						<p className="text-sm text-muted-foreground">
							You have every video. Nice.
						</p>
					)}
				</GlassCard>
			</aside>

			{/* Floating create and chat buttons, stacked above the user avatar */}
			{auth && (
				<div className="fixed bottom-36 right-4 z-40 flex flex-col gap-3 md:bottom-20">
					{isMusician && (
						<>
							<Link
								href="/story/create"
								variant="outline"
								size="icon"
								aria-label="Create story"
								className="size-12 bg-background/80 shadow-lg backdrop-blur-sm">
								<Camera className="size-5" />
							</Link>
							<Link
								href="/post/create"
								variant="solid"
								size="icon"
								aria-label="Create post"
								className="size-12 shadow-lg shadow-primary/30">
								<PenLine className="size-5" />
							</Link>
						</>
					)}
					<Link
						href="/chat"
						variant="outline"
						size="icon"
						aria-label="Chat"
						className="size-12 bg-background/80 shadow-lg backdrop-blur-sm">
						<MessagesSquare className="size-5" />
					</Link>
				</div>
			)}
		</div>
	)
}
