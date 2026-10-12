// Shapes returned by the karaoke API (KaraokeResource, SavedKaraokeResource
// and the karaoke-audios listing).

/** KaraokeResource (also SavedKaraokeResource, which sends createdAt instead). */
export type Karaoke = {
	id: string
	/** Video file path, use mediaUrl() for the src. */
	karaoke: string
	/** The song sung over (an Audio id). */
	audioId: string
	audioName: string
	audioThumbnail: string
	name: string
	username: string
	avatar: string
	decos: number
	description: string
	hasLiked: boolean
	hasSaved: boolean
	likes: number
	comments: number
	created_at?: string
	createdAt?: string
}

/** A row of GET api/karaoke-audios: a song that is open for karaoke. */
export type KaraokeAudio = {
	id: string
	audio_id: string
	user_id: string
	created_at: string
	audio: { id: string; name: string; thumbnail: string } | null
}
