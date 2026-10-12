import type { Video } from "@/types/music"

/**
 * The full VideoResource. `thumbnail` is null until the server makes one from
 * the video when the artist didn't upload a thumbnail.
 */
export type VideoDetail = Omit<Video, "thumbnail"> & {
	thumbnail: string | null
	videoAlbumId: string
	album: string | null
	genre: string | null
	description: string | null
	/** "12 Oct 2026". */
	released: string
}

/** VideoAlbumResource. */
export type VideoAlbum = {
	id: string
	username: string
	name: string
	cover: string
	/** "12 Oct 2026". */
	released: string
	createdAt: string
}

/** A chart artist (UserResource, trimmed to what the charts use). */
export type ChartArtist = {
	id: string
	name: string
	username: string
	avatar: string
	decos: number
}

/** The video-charts/* endpoints. */
export type VideoChart = {
	artists: ChartArtist[]
	videos: VideoDetail[]
}

export const VIDEO_CHARTS = [
	{ slug: "newly-released", label: "Newly Released" },
	{ slug: "trending", label: "Trending" },
	{ slug: "top-downloaded", label: "Top Downloaded" },
	{ slug: "top-liked", label: "Top Liked" },
] as const

export type VideoChartSlug = (typeof VIDEO_CHARTS)[number]["slug"]

export const VIDEO_GENRES = [
	"Afro",
	"Benga",
	"Blues",
	"Boomba",
	"Country",
	"Cultural",
	"EDM",
	"Genge",
	"Gospel",
	"Hiphop",
	"Jazz",
	"Music of Kenya",
	"Pop",
	"R&B",
	"Rock",
	"Sesube",
	"Taarab",
] as const

/** Videos sell at KES 20, half of which goes to the musician. */
export const VIDEO_PRICE = 20
export const VIDEO_ARTIST_SHARE = 10

/** BoughtVideoResource: `id` is the video's id. */
export type BoughtVideo = Pick<
	VideoDetail,
	| "id"
	| "video"
	| "name"
	| "artistName"
	| "username"
	| "avatar"
	| "artistDecos"
	| "ft"
	| "videoAlbumId"
	| "album"
	| "genre"
	| "thumbnail"
	| "description"
	| "released"
	| "hasBoughtVideo"
	| "createdAt"
>
