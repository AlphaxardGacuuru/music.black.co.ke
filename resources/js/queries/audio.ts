import { useQuery } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import { downloadSong } from "@/lib/download"
import type {
	AudioAlbum,
	AudioChart,
	AudioChartName,
	AudioForm,
	BoughtAudio,
} from "@/types/audio"
import type { Audio } from "@/types/music"

export const audioKeys = {
	all: ["audios"] as const,
	list: ["audios", "list"] as const,
	show: (id: string) => ["audios", "show", id] as const,
	chart: (chart: AudioChartName) => ["audios", "chart", chart] as const,
	bought: ["audios", "bought"] as const,
	artistAudios: (username: string) =>
		["audios", "artist", username, "audios"] as const,
	artistAlbums: (username: string) =>
		["audios", "artist", username, "albums"] as const,
	artistBought: (username: string) =>
		["audios", "artist", username, "bought"] as const,
	album: (id: string) => ["audios", "album", id] as const,
}

/** Every song, used for "songs to listen to" suggestions. */
export function useAudios() {
	return useQuery({
		queryKey: audioKeys.list,
		queryFn: () =>
			Axios.get<{ data: Audio[] }>("api/audios").then((res) => res.data.data),
	})
}

export function useAudio(id: string) {
	return useQuery({
		queryKey: audioKeys.show(id),
		queryFn: () =>
			Axios.get<{ data: Audio }>(`api/audios/${id}`).then(
				(res) => res.data.data
			),
	})
}

export function useAudioChart(chart: AudioChartName) {
	return useQuery({
		queryKey: audioKeys.chart(chart),
		queryFn: () =>
			Axios.get<{ data: AudioChart }>(`api/audio-charts/${chart}`).then(
				(res) => res.data.data
			),
	})
}

/** The signed-in user's bought songs (empty for guests). */
export function useBoughtAudios(enabled = true) {
	return useQuery({
		queryKey: audioKeys.bought,
		enabled,
		queryFn: () =>
			Axios.get<{ data: BoughtAudio[] }>("api/bought-audios").then(
				(res) => res.data.data
			),
	})
}

export function useArtistAudios(username: string | undefined) {
	return useQuery({
		queryKey: audioKeys.artistAudios(username ?? ""),
		enabled: Boolean(username),
		queryFn: () =>
			Axios.get<{ data: Audio[] }>(`api/artist/audios/${username}`).then(
				(res) => res.data.data
			),
	})
}

export function useArtistAudioAlbums(username: string | undefined) {
	return useQuery({
		queryKey: audioKeys.artistAlbums(username ?? ""),
		enabled: Boolean(username),
		queryFn: () =>
			Axios.get<{ data: AudioAlbum[] }>(
				`api/artist/audio-albums/${username}`
			).then((res) => res.data.data),
	})
}

/** Every purchase of the musician's songs (one row per sale). */
export function useArtistBoughtAudios(username: string | undefined) {
	return useQuery({
		queryKey: audioKeys.artistBought(username ?? ""),
		enabled: Boolean(username),
		queryFn: () =>
			Axios.get<{ data: BoughtAudio[] }>(
				`api/artist/bought-audios/${username}`
			).then((res) => res.data.data),
	})
}

export function useAudioAlbum(id: string) {
	return useQuery({
		queryKey: audioKeys.album(id),
		queryFn: () =>
			Axios.get<{ data: AudioAlbum }>(`api/audio-albums/${id}`).then(
				(res) => res.data.data
			),
	})
}

type MessageResponse = { message?: string }

/** Album fields; the cover is an image file sent as multipart. */
export type AudioAlbumForm = {
	name?: string
	released?: string
	cover?: File | null
}

function albumFormData(form: AudioAlbumForm): FormData {
	const data = new FormData()

	if (form.name) {
		data.append("name", form.name)
	}

	if (form.released) {
		data.append("released", form.released)
	}

	if (form.cover) {
		data.append("cover", form.cover)
	}

	return data
}

export const audioActions = {
	like: (audioId: string) =>
		Axios.post<MessageResponse>("api/audio-likes", { audio: audioId }),
	cart: (audioId: string) =>
		Axios.post<MessageResponse>("api/cart-audios", { audio: audioId }),
	follow: (username: string) =>
		Axios.post<MessageResponse>("api/follows", { musician: username }),
	create: (form: AudioForm) =>
		Axios.post<MessageResponse>("api/audios", form),
	update: (id: string, form: AudioForm) =>
		Axios.put<MessageResponse>(`api/audios/${id}`, form),
	createAlbum: (form: AudioAlbumForm) =>
		Axios.post<MessageResponse>("api/audio-albums", albumFormData(form)),
	updateAlbum: (id: string, form: AudioAlbumForm) => {
		const data = albumFormData(form)
		// PHP only parses multipart bodies on POST, so spoof the PUT.
		data.append("_method", "PUT")

		return Axios.post<MessageResponse>(`api/audio-albums/${id}`, data)
	},
	/** Download a bought song as a file named after it. */
	download: (audio: Pick<Audio, "id">) => downloadSong("audio", audio.id),
}

/** BoughtAudioResource lacks the per-viewer counts; fill them for the player. */
export function boughtToAudio(bought: BoughtAudio): Audio {
	return {
		...bought,
		hasLiked: false,
		likes: 0,
		comments: 0,
		inCart: false,
		hasBought1: true,
		hasFollowed: false,
		downloads: 0,
	}
}

export const AUDIO_PRICE = 10

/** The musician's share of each sale, in KES. */
export const AUDIO_ARTIST_SHARE = 5

export const GENRES = [
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
