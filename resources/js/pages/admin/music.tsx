import type { ColumnDef } from "@tanstack/react-table"
import {
	Clapperboard,
	Headphones,
	MicVocal,
	ShoppingBag,
	User,
	Users,
} from "lucide-react"
import { useState } from "react"
import AdminStatCard from "@/components/admin/AdminStatCard"
import { KaraokeAudioForm } from "@/components/admin/music/karaoke-audio-form"
import { SalesCard } from "@/components/admin/music/sales-card"
import Heading from "@/components/heading"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { DataTable } from "@/components/ui/data-table"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { Head } from "@/lib/spa"
import {
	useAdminAudios,
	useAdminMusicStats,
	useAdminSongPayouts,
	useAdminVideos,
} from "@/queries/commerce"
import {
	AUDIO_PRICE,
	type AdminSongPayout,
	type AdminVideo,
	VIDEO_PRICE,
} from "@/types/commerce"
import type { Audio } from "@/types/music"

function formatDate(value: string): string {
	return new Date(value).toLocaleDateString()
}

const videoColumns: ColumnDef<AdminVideo>[] = [
	{
		accessorKey: "name",
		header: "Name",
		cell: ({ row }) => (
			<Link
				href={`/video/show/${row.original.id}`}
				variant="text">
				{row.original.name}
			</Link>
		),
	},
	{ accessorKey: "username", header: "Artist" },
	{ accessorKey: "genre", header: "Genre" },
	{ accessorKey: "downloads", header: "Sold" },
	{ accessorKey: "likes", header: "Likes" },
	{
		accessorKey: "createdAt",
		header: "Uploaded",
		cell: ({ row }) => formatDate(row.original.createdAt),
	},
]

const audioColumns: ColumnDef<Audio>[] = [
	{
		accessorKey: "name",
		header: "Name",
		cell: ({ row }) => (
			<Link
				href={`/audio/show/${row.original.id}`}
				variant="text">
				{row.original.name}
			</Link>
		),
	},
	{ accessorKey: "username", header: "Artist" },
	{ accessorKey: "genre", header: "Genre" },
	{ accessorKey: "downloads", header: "Sold" },
	{ accessorKey: "likes", header: "Likes" },
	{
		accessorKey: "createdAt",
		header: "Uploaded",
		cell: ({ row }) => formatDate(row.original.createdAt),
	},
]

const payoutColumns: ColumnDef<AdminSongPayout>[] = [
	{
		accessorKey: "username",
		header: "Artist",
		cell: ({ row }) => row.original.username ?? "Deleted user",
	},
	{
		accessorKey: "amount",
		header: "Amount",
		cell: ({ row }) => `KES ${row.original.amount}`,
	},
	{
		accessorKey: "created_at",
		header: "Requested",
		cell: ({ row }) => formatDate(row.original.created_at),
	},
]

function matches(search: string, ...values: (string | null | undefined)[]) {
	const needle = search.trim().toLowerCase()

	return !needle || values.some((value) => value?.toLowerCase().includes(needle))
}

/** OLD /admin: music counts, sales, every video and audio, song payouts. */
export default function AdminMusic() {
	const stats = useAdminMusicStats()
	const videos = useAdminVideos()
	const audios = useAdminAudios()
	const payouts = useAdminSongPayouts()

	const [videoSearch, setVideoSearch] = useState("")
	const [audioSearch, setAudioSearch] = useState("")

	const filteredVideos = (videos.data ?? []).filter((video) =>
		matches(videoSearch, video.name, video.username)
	)
	const filteredAudios = (audios.data ?? []).filter((audio) =>
		matches(audioSearch, audio.name, audio.username)
	)

	return (
		<>
			<Head title="Music | Admin" />

			<div className="space-y-6">
				<Heading
					variant="small"
					title="Music"
					description="Users, songs, sales and artist payouts"
				/>

				{stats.isPending || !stats.data ? (
					<div className="grid grid-cols-2 gap-4 lg:grid-cols-5">
						{Array.from({ length: 5 }).map((_, index) => (
							<Skeleton
								key={index}
								className="h-20"
							/>
						))}
					</div>
				) : (
					<>
						<div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
							<AdminStatCard
								label="Users"
								value={stats.data.users}
								icon={Users}
							/>
							<AdminStatCard
								label="Fans"
								value={stats.data.normal}
								icon={User}
							/>
							<AdminStatCard
								label="Musicians"
								value={stats.data.musicians}
								icon={MicVocal}
							/>
							<AdminStatCard
								label="Videos"
								value={stats.data.videos}
								icon={Clapperboard}
							/>
							<AdminStatCard
								label="Audios"
								value={stats.data.audios}
								icon={Headphones}
							/>
						</div>

						<div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
							<SalesCard
								label="Videos bought"
								sold={stats.data.boughtVideos}
								price={VIDEO_PRICE}
								icon={ShoppingBag}
							/>
							<SalesCard
								label="Audios bought"
								sold={stats.data.boughtAudios}
								price={AUDIO_PRICE}
								icon={ShoppingBag}
							/>
						</div>
					</>
				)}

				<p className="text-sm text-muted-foreground">
					User details live in{" "}
					<Link
						href="/admin/users"
						variant="text">
						Users
					</Link>{" "}
					and Kopokopo recipients in{" "}
					<Link
						href="/admin/kopokopo-transfers"
						variant="text">
						Payouts
					</Link>
					.
				</p>

				<Card className="overflow-hidden">
					<CardHeader className="gap-3 pb-4 sm:flex-row sm:items-center sm:justify-between">
						<CardTitle>Videos</CardTitle>
						<Input
							value={videoSearch}
							onChange={(event) => setVideoSearch(event.target.value)}
							placeholder="Search videos or artists"
							className="sm:max-w-xs"
						/>
					</CardHeader>
					<CardContent>
						<DataTable
							columns={videoColumns}
							data={filteredVideos}
							emptyMessage={videos.isPending ? "Loading..." : "No videos."}
						/>
					</CardContent>
				</Card>

				<Card className="overflow-hidden">
					<CardHeader className="gap-3 pb-4 sm:flex-row sm:items-center sm:justify-between">
						<CardTitle>Audios</CardTitle>
						<Input
							value={audioSearch}
							onChange={(event) => setAudioSearch(event.target.value)}
							placeholder="Search audios or artists"
							className="sm:max-w-xs"
						/>
					</CardHeader>
					<CardContent>
						<DataTable
							columns={audioColumns}
							data={filteredAudios}
							emptyMessage={audios.isPending ? "Loading..." : "No audios."}
						/>
					</CardContent>
				</Card>

				<Card className="overflow-hidden">
					<CardHeader className="pb-4">
						<CardTitle>Song payouts</CardTitle>
					</CardHeader>
					<CardContent>
						<DataTable
							columns={payoutColumns}
							data={payouts.data ?? []}
							emptyMessage={payouts.isPending ? "Loading..." : "No payouts yet."}
						/>
					</CardContent>
				</Card>

				<Card>
					<CardHeader className="pb-4">
						<CardTitle>Karaoke audio</CardTitle>
						<p className="text-sm text-muted-foreground">
							Offer an audio as a backing track people can sing karaoke to.
						</p>
					</CardHeader>
					<CardContent>
						<KaraokeAudioForm audios={audios.data ?? []} />
					</CardContent>
				</Card>
			</div>
		</>
	)
}
