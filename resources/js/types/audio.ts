// Audio API shapes beyond the Audio type in types/music.ts.
import type { Artist, Audio } from "@/types/music"

/** AudioAlbumResource. */
export type AudioAlbum = {
	id: string
	username: string | null
	name: string
	cover: string
	released: string | null
	createdAt: string
}

/** BoughtAudioResource: the bought song, without the per-viewer counts. */
export type BoughtAudio = Pick<
	Audio,
	| "id"
	| "audio"
	| "name"
	| "artistName"
	| "username"
	| "avatar"
	| "artistDecos"
	| "ft"
	| "audioAlbumId"
	| "album"
	| "genre"
	| "thumbnail"
	| "description"
	| "released"
	| "createdAt"
> & { hasBoughtAudio: true }

/** What /api/audio-charts/<chart> returns under `data`. */
export type AudioChart = {
	/** Musicians in the chart, most songs first (UserResource). */
	artists: Artist[]
	audios: Audio[]
}

export type AudioChartName =
	| "newly-released"
	| "trending"
	| "top-downloaded"
	| "top-liked"

/** Fields sent to POST /api/audios and PUT /api/audios/{id}. */
export type AudioForm = {
	audio?: string
	thumbnail?: string
	name?: string
	ft?: string
	audio_album_id?: string
	genre?: string
	released?: string
	description?: string
}
