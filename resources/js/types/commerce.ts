// Shapes returned by the commerce API: carts (CartVideoResource,
// CartAudioResource), bought songs (BoughtVideoResource, BoughtAudioResource),
// saved karaokes, song payouts and the admin music endpoints.

import type { Audio, Video } from "@/types/music"

/** Price of one video, in KES (BoughtVideo::PRICE). */
export const VIDEO_PRICE = 20

/** Price of one audio, in KES (BoughtAudio::PRICE). */
export const AUDIO_PRICE = 10

/** A video in the cart: the VideoResource fields plus the cart row id. */
export type CartVideo = Video & {
	cartId: string
	videoAlbumId: string
	album: string | null
	genre: string
	description: string | null
	released: string
}

/** An audio in the cart: the AudioResource fields plus the cart row id. */
export type CartAudio = Audio & { cartId: string }

/** BoughtVideoResource, also the receipt shape in VideoBoughtEvent. */
export type BoughtVideo = {
	id: string
	video: string
	name: string
	artistName: string
	username: string
	avatar: string
	artistDecos: number
	ft: string | null
	videoAlbumId: string
	album: string | null
	genre: string
	thumbnail: string
	description: string | null
	released: string
	hasBoughtVideo: true
	createdAt: string
}

/** BoughtAudioResource, also the receipt shape in AudioBoughtEvent. */
export type BoughtAudio = {
	id: string
	audio: string
	name: string
	artistName: string
	username: string
	avatar: string
	artistDecos: number
	ft: string | null
	audioAlbumId: string
	album: string | null
	genre: string
	thumbnail: string
	description: string | null
	released: string
	hasBoughtAudio: true
	createdAt: string
}

/** Broadcast on the "video-bought" channel once a payment buys videos. */
export type VideoBoughtPayload = {
	structuredVideos: BoughtVideo[]
	decoArtists: string[]
	user: { id: string }
}

/** Broadcast on the "audio-bought" channel once a payment buys audios. */
export type AudioBoughtPayload = {
	structuredAudios: BoughtAudio[]
	decoArtists: string[]
	user: { id: string }
}

/** One row of GET api/song-payouts. */
export type SongPayout = {
	id: string
	/** What was taken off the balance, in KES. */
	amount: string
	/** The provider charge kept back, in KES. */
	fee: number
	/** What reached M-Pesa, in KES. */
	sent: number
	status: "paid" | "pending"
	paidAt: string | null
	created_at: string
}

export type SongPayouts = {
	songPayouts: SongPayout[]
	/** What the artist can withdraw now, in KES. */
	balance: number
}

/** Withdrawals below this (KES) have SMALL_PAYOUT_FEE kept back. */
export const SMALL_PAYOUT_LIMIT = 1000

/** The provider charge on a small withdrawal, in KES. */
export const SMALL_PAYOUT_FEE = 50

/** GET api/admin/admin. */
export type AdminMusicStats = {
	normal: number
	musicians: number
	users: number
	videos: number
	boughtVideos: number
	audios: number
	boughtAudios: number
}

/** One row of GET api/admin/song-payouts. */
export type AdminSongPayout = {
	id: string
	username: string | null
	amount: string
	created_at: string
	updated_at: string
}

/** VideoResource as listed by GET api/admin/videos. */
export type AdminVideo = Video & {
	album: string | null
	genre: string
	released: string
}
