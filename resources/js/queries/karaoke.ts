import { useQuery } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import type { Karaoke, KaraokeAudio } from "@/types/karaoke"
import type { Audio } from "@/types/music"

export const karaokeKeys = {
	all: ["karaokes"] as const,
	list: ["karaokes", "list"] as const,
	one: (id: string) => ["karaokes", "one", id] as const,
	saved: ["karaokes", "saved"] as const,
	audios: ["karaoke-audios"] as const,
	backingTrack: (audioId: string) => ["karaoke-backing-track", audioId] as const,
}

/** Every karaoke, newest first (the API sends them oldest first, unpaginated). */
export function useKaraokes() {
	return useQuery({
		queryKey: karaokeKeys.list,
		queryFn: () =>
			Axios.get<{ data: Karaoke[] }>("api/karaokes").then((res) =>
				[...res.data.data].reverse()
			),
	})
}

export function useKaraoke(id: string) {
	return useQuery({
		queryKey: karaokeKeys.one(id),
		queryFn: () =>
			Axios.get<{ data: Karaoke }>(`api/karaokes/${id}`).then(
				(res) => res.data.data
			),
	})
}

/** The signed-in user's saved karaokes, newest save first. */
export function useSavedKaraokes(enabled = true) {
	return useQuery({
		queryKey: karaokeKeys.saved,
		enabled,
		queryFn: () =>
			Axios.get<{ data: Karaoke[] }>("api/saved-karaokes").then((res) =>
				[...res.data.data].reverse()
			),
	})
}

/** Songs open for karaoke, one entry per song. */
export function useKaraokeAudios() {
	return useQuery({
		queryKey: karaokeKeys.audios,
		queryFn: () =>
			Axios.get<KaraokeAudio[]>("api/karaoke-audios").then((res) => {
				const seen = new Set<string>()

				return res.data.filter((row) => {
					if (seen.has(row.audio_id)) {
						return false
					}

					seen.add(row.audio_id)

					return true
				})
			}),
	})
}

/** The song a karaoke is recorded over. */
export function useBackingTrack(audioId: string) {
	return useQuery({
		queryKey: karaokeKeys.backingTrack(audioId),
		queryFn: () =>
			Axios.get<{ data: Audio }>(`api/audios/${audioId}`).then(
				(res) => res.data.data
			),
	})
}

type MessageResponse = { message?: string }

export const karaokeActions = {
	like: (karaokeId: string) =>
		Axios.post<MessageResponse>("api/karaoke-likes", { karaoke: karaokeId }),
	save: (karaokeId: string) =>
		Axios.post<MessageResponse>("api/saved-karaokes", { id: karaokeId }),
	/** Upload a recorded or picked video; resolves to the stored path. */
	uploadVideo: (
		file: Blob,
		fileName: string,
		onProgress?: (percent: number) => void
	) => {
		const data = new FormData()
		data.append("filepond-karaoke", file, fileName)

		return Axios.post<string>("api/filepond/karaokes", data, {
			onUploadProgress: (event) =>
				event.total &&
				onProgress?.(Math.round((event.loaded / event.total) * 100)),
		}).then((res) => res.data)
	},
	create: (data: { karaoke: string; audio_id: string; description: string }) =>
		Axios.post<MessageResponse>("api/karaokes", data),
}
