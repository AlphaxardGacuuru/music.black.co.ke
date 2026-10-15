import { useState } from "react"
import { PostCard } from "@/components/feed/post-card"
import { VideoCard } from "@/components/feed/video-card"
import { AlbumHeader } from "@/components/profile/album-header"
import { AudioRow } from "@/components/profile/audio-row"
import { ProfileHeader } from "@/components/profile/profile-header"
import { GlassCard } from "@/components/ui/glass-card"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import { cn } from "@/lib/utils"
import {
	useBought,
	useProfile,
	useProfileAudios,
	useProfilePosts,
	useProfileVideos,
} from "@/queries/social"
import type { Album } from "@/types/social"

export const isPublic = true

type Tab = "videos" | "posts" | "audios" | "bought"

export default function ProfileShow({ username }: { username: string }) {
	const { auth } = useApp()
	const profile = useProfile(username)
	const isOwn = auth?.username === username

	return (
		<>
			<Head title={`${profile.data?.name ?? username} | Black Music`} />
			<div className="mx-auto w-full max-w-4xl space-y-6 px-4 py-6">
				{profile.isPending && (
					<GlassCard className="overflow-hidden">
						<Skeleton className="h-36 rounded-none sm:h-52" />
						<div className="space-y-3 p-5">
							<Skeleton className="size-24 rounded-full" />
							<Skeleton className="h-6 w-48" />
							<Skeleton className="h-4 w-32" />
						</div>
					</GlassCard>
				)}

				{profile.isError && (
					<GlassCard className="p-8 text-center text-sm text-muted-foreground">
						There's no one called {username} here.
					</GlassCard>
				)}

				{profile.data && (
					<>
						<ProfileHeader
							key={profile.dataUpdatedAt}
							user={profile.data}
						/>
						<ProfileTabs
							username={username}
							isOwn={isOwn}
							isMusician={profile.data.accountType === "musician"}
						/>
					</>
				)}
			</div>
		</>
	)
}

function ProfileTabs({
	username,
	isOwn,
	isMusician,
}: {
	username: string
	isOwn: boolean
	isMusician: boolean
}) {
	const tabs: { id: Tab; label: string }[] = [
		{ id: "videos", label: "Videos" },
		{ id: "posts", label: "Posts" },
		{ id: "audios", label: "Audios" },
		...(isOwn ? [{ id: "bought" as const, label: "Bought" }] : []),
	]

	const [tab, setTab] = useState<Tab>(
		isMusician ? "videos" : isOwn ? "bought" : "posts"
	)

	return (
		<section className="space-y-4">
			<div
				role="tablist"
				className="grid gap-1 rounded-full border border-border/60 bg-background/50 p-1 backdrop-blur"
				style={{ gridTemplateColumns: `repeat(${tabs.length}, minmax(0, 1fr))` }}>
				{tabs.map((item) => (
					<button
						key={item.id}
						type="button"
						role="tab"
						aria-selected={tab === item.id}
						onClick={() => setTab(item.id)}
						className={cn(
							"h-9 rounded-full text-sm font-medium transition-colors",
							tab === item.id
								? "bg-primary text-primary-foreground shadow-sm"
								: "text-muted-foreground hover:text-foreground"
						)}>
						{item.label}
					</button>
				))}
			</div>

			{tab === "videos" && <VideosTab username={username} />}
			{tab === "posts" && <PostsTab username={username} />}
			{tab === "audios" && <AudiosTab username={username} />}
			{tab === "bought" && isOwn && <BoughtTab />}
		</section>
	)
}

function Empty({ children }: { children: string }) {
	return (
		<GlassCard className="p-8 text-center text-sm text-muted-foreground">
			{children}
		</GlassCard>
	)
}

function ListSkeleton() {
	return (
		<GlassCard className="space-y-4 p-5">
			{[1, 2, 3].map((key) => (
				<div
					key={key}
					className="flex gap-3">
					<Skeleton className="aspect-video w-36 rounded-xl" />
					<Skeleton className="h-4 flex-1" />
				</div>
			))}
		</GlassCard>
	)
}

/** Albums that have songs, in order, each with its songs. */
function byAlbum<T>(albums: Album[], items: T[], albumId: (item: T) => string) {
	return albums
		.map((album) => ({
			album,
			items: items.filter((item) => albumId(item) === album.id),
		}))
		.filter((group) => group.items.length > 0)
}

function VideosTab({ username }: { username: string }) {
	const videos = useProfileVideos(username)

	if (videos.isPending) {
		return <ListSkeleton />
	}

	const groups = byAlbum(
		videos.data?.albums ?? [],
		videos.data?.videos ?? [],
		(video) => video.videoAlbumId
	)

	if (groups.length === 0) {
		return <Empty>{`${username} does not have any videos`}</Empty>
	}

	return (
		<div className="grid gap-4 md:grid-cols-2">
			{groups.map(({ album, items }) => (
				<GlassCard
					key={album.id}
					className="space-y-4 p-4 sm:p-5">
					<AlbumHeader
						album={album}
						kind="Video"
					/>
					{items.map((video) => (
						<VideoCard
							key={video.id}
							video={video}
						/>
					))}
				</GlassCard>
			))}
		</div>
	)
}

function AudiosTab({ username }: { username: string }) {
	const audios = useProfileAudios(username)

	if (audios.isPending) {
		return <ListSkeleton />
	}

	const all = audios.data?.audios ?? []
	const groups = byAlbum(
		audios.data?.albums ?? [],
		all,
		(audio) => audio.audioAlbumId
	)

	if (groups.length === 0) {
		return <Empty>{`${username} does not have any audios`}</Empty>
	}

	return (
		<div className="grid gap-4 md:grid-cols-2">
			{groups.map(({ album, items }) => (
				<GlassCard
					key={album.id}
					className="space-y-4 p-4 sm:p-5">
					<AlbumHeader
						album={album}
						kind="Audio"
					/>
					{items.map((audio) => (
						<AudioRow
							key={audio.id}
							audio={audio}
							queue={items}
						/>
					))}
				</GlassCard>
			))}
		</div>
	)
}

function PostsTab({ username }: { username: string }) {
	const { auth } = useApp()
	const posts = useProfilePosts(username)
	const [deletedIds, setDeletedIds] = useState<string[]>([])

	if (posts.isPending) {
		return <ListSkeleton />
	}

	const list = (posts.data ?? []).filter((post) => !deletedIds.includes(post.id))

	if (list.length === 0) {
		return (
			<Empty>
				{auth
					? `No posts to show. Follow ${username} to see their posts.`
					: `Sign in and follow ${username} to see their posts.`}
			</Empty>
		)
	}

	return (
		<div className="mx-auto max-w-xl space-y-4">
			{list.map((post) => (
				<PostCard
					key={post.id}
					post={post}
					onDeleted={(id) => setDeletedIds((ids) => [...ids, id])}
				/>
			))}
		</div>
	)
}

function BoughtTab() {
	const bought = useBought(true)

	if (bought.isPending) {
		return <ListSkeleton />
	}

	const videos = bought.data?.videos ?? []
	const audios = bought.data?.audios ?? []

	if (videos.length === 0 && audios.length === 0) {
		return <Empty>You haven't bought any songs yet.</Empty>
	}

	return (
		<div className="grid gap-4 md:grid-cols-2">
			<GlassCard className="space-y-4 p-4 sm:p-5">
				<h2 className="font-semibold">Videos</h2>
				{videos.length === 0 && (
					<p className="text-sm text-muted-foreground">No videos yet.</p>
				)}
				{videos.map((video) => (
					<VideoCard
						key={video.id}
						video={video}
					/>
				))}
			</GlassCard>
			<GlassCard className="space-y-4 p-4 sm:p-5">
				<h2 className="font-semibold">Audios</h2>
				{audios.length === 0 && (
					<p className="text-sm text-muted-foreground">No audios yet.</p>
				)}
				{audios.map((audio) => (
					<AudioRow
						key={audio.id}
						audio={audio}
						queue={audios}
					/>
				))}
			</GlassCard>
		</div>
	)
}
