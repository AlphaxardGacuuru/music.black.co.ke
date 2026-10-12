import { useQuery } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import type {
	AdminMusicStats,
	AdminSongPayout,
	AdminVideo,
	BoughtAudio,
	BoughtVideo,
	CartAudio,
	CartVideo,
	SongPayouts,
} from "@/types/commerce"
import type { Audio } from "@/types/music"

export const commerceKeys = {
	cartVideos: ["cart-videos"] as const,
	cartAudios: ["cart-audios"] as const,
	boughtVideos: ["bought-videos"] as const,
	boughtAudios: ["bought-audios"] as const,
	songPayouts: ["song-payouts"] as const,
	adminStats: ["admin", "music", "stats"] as const,
	adminVideos: ["admin", "music", "videos"] as const,
	adminAudios: ["admin", "music", "audios"] as const,
	adminSongPayouts: ["admin", "music", "song-payouts"] as const,
	auth: ["auth"] as const,
}

export function useCartVideos() {
	return useQuery({
		queryKey: commerceKeys.cartVideos,
		queryFn: () =>
			Axios.get<{ data: CartVideo[] }>("api/cart-videos").then(
				(res) => res.data.data
			),
	})
}

export function useCartAudios() {
	return useQuery({
		queryKey: commerceKeys.cartAudios,
		queryFn: () =>
			Axios.get<{ data: CartAudio[] }>("api/cart-audios").then(
				(res) => res.data.data
			),
	})
}

export function useBoughtVideos() {
	return useQuery({
		queryKey: commerceKeys.boughtVideos,
		queryFn: () =>
			Axios.get<{ data: BoughtVideo[] }>("api/bought-videos").then(
				(res) => res.data.data
			),
	})
}

export function useBoughtAudios() {
	return useQuery({
		queryKey: commerceKeys.boughtAudios,
		queryFn: () =>
			Axios.get<{ data: BoughtAudio[] }>("api/bought-audios").then(
				(res) => res.data.data
			),
	})
}

export function useSongPayouts() {
	return useQuery({
		queryKey: commerceKeys.songPayouts,
		queryFn: () =>
			Axios.get<{ data: SongPayouts }>("api/song-payouts").then(
				(res) => res.data.data
			),
	})
}

export function useAdminMusicStats() {
	return useQuery({
		queryKey: commerceKeys.adminStats,
		queryFn: () =>
			Axios.get<{ data: AdminMusicStats }>("api/admin/admin").then(
				(res) => res.data.data
			),
	})
}

export function useAdminVideos() {
	return useQuery({
		queryKey: commerceKeys.adminVideos,
		queryFn: () =>
			Axios.get<{ data: AdminVideo[] }>("api/admin/videos").then(
				(res) => res.data.data
			),
	})
}

export function useAdminAudios() {
	return useQuery({
		queryKey: commerceKeys.adminAudios,
		queryFn: () =>
			Axios.get<{ data: Audio[] }>("api/admin/audios").then(
				(res) => res.data.data
			),
	})
}

export function useAdminSongPayouts() {
	return useQuery({
		queryKey: commerceKeys.adminSongPayouts,
		queryFn: () =>
			Axios.get<{ data: AdminSongPayout[] }>("api/admin/song-payouts").then(
				(res) => res.data.data
			),
	})
}

type MessageResponse = { message?: string }

export const commerceActions = {
	/** Toggle a video in or out of the cart. */
	cartVideo: (videoId: string) =>
		Axios.post<MessageResponse>("api/cart-videos", { video: videoId }),
	/** Toggle an audio in or out of the cart. */
	cartAudio: (audioId: string) =>
		Axios.post<MessageResponse>("api/cart-audios", { audio: audioId }),
	/** Ask M-Pesa to prompt the account's phone to pay `amount` KES. */
	stkPush: (amount: number) =>
		Axios.post<MessageResponse>("api/stk-push", { amount }),
	/** Buy the cart with the balance already in the wallet. */
	checkout: () =>
		Axios.post<MessageResponse & { data: { videos: BoughtVideo[]; audios: BoughtAudio[] } }>(
			"api/cart-checkout"
		),
	/** Withdraw the artist's whole balance to the M-Pesa number on the account. */
	withdraw: () => Axios.post<MessageResponse>("api/song-payouts"),
	/** Admin: make an audio available for karaokes. */
	setKaraokeAudio: (audioId: string) =>
		Axios.post<MessageResponse>("api/karaoke-audios", { audio_id: audioId }),
}

/**
 * BoughtAudioResource leaves out the social counters AudioResource has; fill
 * them so bought audios can go through the app-wide player.
 */
export function boughtToAudio(audio: BoughtAudio): Audio {
	return {
		...audio,
		hasLiked: false,
		likes: 0,
		comments: 0,
		inCart: false,
		hasBought1: false,
		hasFollowed: false,
		downloads: 0,
	}
}
