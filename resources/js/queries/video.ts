import { useQuery } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import { downloadSong } from "@/lib/download"
import { mediaUrl } from "@/lib/media"
import type {
	BoughtVideo,
	VideoAlbum,
	VideoChart,
	VideoChartSlug,
	VideoDetail,
} from "@/types/video"

export const videoKeys = {
	all: ["video"] as const,
	chart: (slug: VideoChartSlug) => ["video", "chart", slug] as const,
	list: ["video", "list"] as const,
	show: (id: string) => ["video", "show", id] as const,
	bought: ["video", "bought"] as const,
	artistVideos: (username: string) =>
		["video", "artist-videos", username] as const,
	artistAlbums: (username: string) =>
		["video", "artist-albums", username] as const,
	artistBought: (username: string) =>
		["video", "artist-bought", username] as const,
	album: (id: string) => ["video", "album", id] as const,
}

export function useVideoChart(slug: VideoChartSlug) {
	return useQuery({
		queryKey: videoKeys.chart(slug),
		queryFn: () =>
			Axios.get<{ data: VideoChart }>(`api/video-charts/${slug}`).then(
				(res) => res.data.data
			),
	})
}

/** Every video (the "songs to watch" list). */
export function useAllVideos() {
	return useQuery({
		queryKey: videoKeys.list,
		queryFn: () =>
			Axios.get<{ data: VideoDetail[] }>("api/videos").then(
				(res) => res.data.data
			),
	})
}

export function useVideo(id: string) {
	return useQuery({
		queryKey: videoKeys.show(id),
		queryFn: () =>
			Axios.get<{ data: VideoDetail }>(`api/videos/${id}`).then(
				(res) => res.data.data
			),
	})
}

/** The signed-in user's bought videos (empty for guests, so skip them). */
export function useBoughtVideos(enabled: boolean) {
	return useQuery({
		queryKey: videoKeys.bought,
		enabled,
		queryFn: () =>
			Axios.get<{ data: BoughtVideo[] }>("api/bought-videos").then(
				(res) => res.data.data
			),
	})
}

export function useArtistVideos(username: string | undefined) {
	return useQuery({
		queryKey: videoKeys.artistVideos(username ?? ""),
		enabled: Boolean(username),
		queryFn: () =>
			Axios.get<{ data: VideoDetail[] }>(
				`api/artist/videos/${username}`
			).then((res) => res.data.data),
	})
}

export function useArtistVideoAlbums(username: string | undefined) {
	return useQuery({
		queryKey: videoKeys.artistAlbums(username ?? ""),
		enabled: Boolean(username),
		queryFn: () =>
			Axios.get<{ data: VideoAlbum[] }>(
				`api/artist/video-albums/${username}`
			).then((res) => res.data.data),
	})
}

/** Sales of an artist's videos, one row per purchase. */
export function useArtistBoughtVideos(username: string | undefined) {
	return useQuery({
		queryKey: videoKeys.artistBought(username ?? ""),
		enabled: Boolean(username),
		queryFn: () =>
			Axios.get<{ data: BoughtVideo[] }>(
				`api/artist/bought-videos/${username}`
			).then((res) => res.data.data),
	})
}

export function useVideoAlbum(id: string) {
	return useQuery({
		queryKey: videoKeys.album(id),
		queryFn: () =>
			Axios.get<{ data: VideoAlbum }>(`api/video-albums/${id}`).then(
				(res) => res.data.data
			),
	})
}

type MessageResponse = { message?: string }

/** The video form's fields, as VideoController reads them. */
export type VideoPayload = {
	video?: string
	thumbnail?: string
	name?: string
	ft?: string
	video_album_id?: string
	genre?: string
	released?: string
	description?: string
}

export const videoActions = {
	like: (videoId: string) =>
		Axios.post<MessageResponse>("api/video-likes", { video: videoId }),
	cart: (videoId: string) =>
		Axios.post<MessageResponse>("api/cart-videos", { video: videoId }),
	follow: (username: string) =>
		Axios.post<MessageResponse>("api/follows", { musician: username }),
	create: (payload: VideoPayload) =>
		Axios.post<MessageResponse & { data: { id: string } }>(
			"api/videos",
			payload
		),
	update: (id: string, payload: VideoPayload) =>
		Axios.put<MessageResponse>(`api/videos/${id}`, payload),
	createAlbum: (data: FormData) =>
		Axios.post<MessageResponse>("api/video-albums", data),
	updateAlbum: (id: string, data: FormData) => {
		data.append("_method", "put")

		return Axios.post<MessageResponse>(`api/video-albums/${id}`, data)
	},
	/** Download a bought video as a file named after it. */
	download: (videoId: string) => downloadSong("video", videoId),
}

/**
 * The API prefixes every video path with "storage/", so a video saved as a
 * link (OLD allowed YouTube links) comes back as "storage/https://...".
 * Returns the playable src and whether it is an embeddable link.
 */
export function videoSource(path: string): { src: string; isLink: boolean } {
	const raw = path.replace(/^\/?storage\/(?=https?:)/, "")
	const isLink = /^https?:/.test(raw)

	return { src: isLink ? raw : mediaUrl(path), isLink }
}

/** "12 Oct 2026" (the API's date format) to "2026-10-12" for date inputs. */
export function toDateInput(value: string | null | undefined): string {
	if (!value) {
		return ""
	}

	const date = new Date(value)

	if (Number.isNaN(date.getTime())) {
		return ""
	}

	const month = String(date.getMonth() + 1).padStart(2, "0")
	const day = String(date.getDate()).padStart(2, "0")

	return `${date.getFullYear()}-${month}-${day}`
}
