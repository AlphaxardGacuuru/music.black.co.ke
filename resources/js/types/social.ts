// Shapes returned by the social API (chats, search history, profiles and
// the albums shown on profiles and in search). Post and Story live in
// types/music.ts.

import type { Audio, Video } from "@/types/music"

/** ChatResource: one message in a conversation. */
export type ChatMessage = {
	id: string
	name: string
	/** The sender's username. */
	username: string
	/** The recipient's username. */
	to: string | null
	avatar: string
	decos: number
	text: string
	/** "/storage/chat-media/..." or null. */
	media: string | null
	createdAt: string
}

/** ChatService::index: the latest message with each person. */
export type ChatThread = {
	id: string
	avatar: string
	name: string
	username: string
	to: string | null
	text: string
	/** The media path of the latest message, if it had one. */
	hasMedia: string | null
	createdAt: string
}

/** SearchService::index: a saved keyword. */
export type SearchHistoryItem = {
	id: string
	username: string
	keyword: string
	created_at: string
	updated_at: string
}

/** UserResource as seen on a public profile. */
export type Profile = {
	id: string
	name: string
	username: string
	avatar: string
	backdrop: string
	bio: string | null
	location: string | null
	withdrawal: string | null
	posts: number
	following: number
	fans: number
	hasFollowed: boolean
	hasBought1: boolean
	decos: number
	videos: number
	boughtVideos: number
	audios: number
	boughtAudios: number
	verified: boolean
	accountType: "normal" | "musician"
}

/** VideoAlbumResource / AudioAlbumResource. */
export type Album = {
	id: string
	username: string | null
	name: string
	cover: string
	released: string
	createdAt: string
}

/** VideoResource also sends the album it belongs to. */
export type AlbumVideo = Video & { videoAlbumId: string }

/** AudioResource already carries audioAlbumId. */
export type AlbumAudio = Audio

/** A story upload: {"image": "stories/x.jpg"} or {"video": "stories/x.mp4"}. */
export type StoryUpload = { image?: string; video?: string }
