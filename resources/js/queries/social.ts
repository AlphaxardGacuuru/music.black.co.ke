import { keepPreviousData, useQuery } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import type { Artist, Audio, Paginated, Post, Story } from "@/types/music"
import type {
	Album,
	AlbumAudio,
	AlbumVideo,
	ChatMessage,
	ChatThread,
	Profile,
	SearchHistoryItem,
} from "@/types/social"

export const socialKeys = {
	post: (id: string) => ["social", "post", id] as const,
	story: (id: string) => ["social", "story", id] as const,
	chatThreads: ["social", "chats"] as const,
	conversation: (username: string) =>
		["social", "chats", username] as const,
	users: (name: string) => ["social", "users", name] as const,
	searchHistory: ["social", "search-history"] as const,
	allArtists: ["social", "all-artists"] as const,
	allAudios: ["social", "all-audios"] as const,
	videoAlbums: ["social", "video-albums"] as const,
	audioAlbums: ["social", "audio-albums"] as const,
	profile: (username: string) => ["social", "profile", username] as const,
	profilePosts: (username: string) =>
		["social", "profile", username, "posts"] as const,
	profileVideos: (username: string) =>
		["social", "profile", username, "videos"] as const,
	profileAudios: (username: string) =>
		["social", "profile", username, "audios"] as const,
	bought: ["social", "bought"] as const,
}

/** Every page of a paginated endpoint, fetched in parallel after the first. */
async function getAllPages<T>(
	url: string,
	params: Record<string, unknown> = {}
): Promise<T[]> {
	const first = await Axios.get<Paginated<T>>(url, {
		params: { ...params, page: 1 },
	}).then((res) => res.data)

	const rest = await Promise.all(
		Array.from({ length: Math.max(0, first.meta.last_page - 1) }, (_, i) =>
			Axios.get<Paginated<T>>(url, { params: { ...params, page: i + 2 } }).then(
				(res) => res.data.data
			)
		)
	)

	return [...first.data, ...rest.flat()]
}

/* Posts and stories */

export function usePost(id: string) {
	return useQuery({
		queryKey: socialKeys.post(id),
		queryFn: () =>
			Axios.get<{ data: Post }>(`api/posts/${id}`).then((res) => res.data.data),
	})
}

export function useStory(id: string) {
	return useQuery({
		queryKey: socialKeys.story(id),
		queryFn: () =>
			Axios.get<{ data: Story }>(`api/stories/${id}`).then(
				(res) => res.data.data
			),
	})
}

/* Chat */

export function useChatThreads() {
	return useQuery({
		queryKey: socialKeys.chatThreads,
		queryFn: () =>
			Axios.get<{ data: ChatThread[] }>("api/chats").then(
				(res) => res.data.data
			),
	})
}

/**
 * The whole conversation with a user, oldest first. The API pages it 10 at a
 * time from the oldest message, so every page is loaded to reach the latest.
 */
export function useConversation(username: string) {
	return useQuery({
		queryKey: socialKeys.conversation(username),
		queryFn: () => getAllPages<ChatMessage>(`api/chats/${username}`),
	})
}

/** People to start a chat with, filtered by name. */
export function useUsers(name: string) {
	return useQuery({
		queryKey: socialKeys.users(name),
		queryFn: () =>
			Axios.get<Paginated<Profile>>("api/users", {
				params: { name: name || undefined, excludeSelf: 1, per_page: 30 },
			}).then((res) => res.data.data),
		placeholderData: keepPreviousData,
	})
}

/* Search */

export function useSearchHistory(enabled: boolean) {
	return useQuery({
		queryKey: socialKeys.searchHistory,
		enabled,
		queryFn: () =>
			Axios.get<SearchHistoryItem[] | { data: SearchHistoryItem[] }>(
				"api/search"
			).then((res) => (Array.isArray(res.data) ? res.data : res.data.data)),
	})
}

export function useAllArtists() {
	return useQuery({
		queryKey: socialKeys.allArtists,
		queryFn: () => getAllPages<Artist>("api/artists"),
	})
}

export function useAllAudios() {
	return useQuery({
		queryKey: socialKeys.allAudios,
		queryFn: () =>
			Axios.get<{ data: Audio[] }>("api/audios").then((res) => res.data.data),
	})
}

export function useVideoAlbums() {
	return useQuery({
		queryKey: socialKeys.videoAlbums,
		queryFn: () =>
			Axios.get<{ data: Album[] }>("api/video-albums").then(
				(res) => res.data.data
			),
	})
}

export function useAudioAlbums() {
	return useQuery({
		queryKey: socialKeys.audioAlbums,
		queryFn: () =>
			Axios.get<{ data: Album[] }>("api/audio-albums").then(
				(res) => res.data.data
			),
	})
}

/* Profiles */

export function useProfile(username: string) {
	return useQuery({
		queryKey: socialKeys.profile(username),
		queryFn: () =>
			Axios.get<{ data: Profile }>(`api/users/${username}`).then(
				(res) => res.data.data
			),
	})
}

/** Only shows posts once the viewer follows the musician (as in OLD). */
export function useProfilePosts(username: string) {
	return useQuery({
		queryKey: socialKeys.profilePosts(username),
		queryFn: () =>
			Axios.get<{ data: Post[] }>(`api/artist/posts/${username}`).then(
				(res) => res.data.data
			),
	})
}

export function useProfileVideos(username: string) {
	return useQuery({
		queryKey: socialKeys.profileVideos(username),
		queryFn: () =>
			Promise.all([
				Axios.get<{ data: Album[] }>(`api/artist/video-albums/${username}`),
				Axios.get<{ data: AlbumVideo[] }>(`api/artist/videos/${username}`),
			]).then(([albums, videos]) => ({
				albums: albums.data.data,
				videos: videos.data.data,
			})),
	})
}

export function useProfileAudios(username: string) {
	return useQuery({
		queryKey: socialKeys.profileAudios(username),
		queryFn: () =>
			Promise.all([
				Axios.get<{ data: Album[] }>(`api/artist/audio-albums/${username}`),
				Axios.get<{ data: AlbumAudio[] }>(`api/artist/audios/${username}`),
			]).then(([albums, audios]) => ({
				albums: albums.data.data,
				audios: audios.data.data,
			})),
	})
}

/** The signed-in user's own bought videos and audios. */
export function useBought(enabled: boolean) {
	return useQuery({
		queryKey: socialKeys.bought,
		enabled,
		queryFn: () =>
			Promise.all([
				Axios.get<{ data: AlbumVideo[] }>("api/bought-videos"),
				Axios.get<{ data: AlbumAudio[] }>("api/bought-audios"),
			]).then(([videos, audios]) => ({
				videos: videos.data.data,
				audios: audios.data.data,
			})),
	})
}

/* Actions */

type MessageResponse = { message?: string }

export type PostInput = {
	text: string
	media?: string
	para1?: string
	para2?: string
	para3?: string
	para4?: string
	para5?: string
}

export const socialActions = {
	createPost: (input: PostInput) =>
		Axios.post<MessageResponse>("api/posts", input),
	updatePost: (id: string, text: string) =>
		Axios.put<MessageResponse>(`api/posts/${id}`, { text }),
	deletePost: (id: string) =>
		Axios.delete<MessageResponse>(`api/posts/${id}`),
	/** `media` is the comma separated list of story upload JSON strings. */
	createStory: (media: string, text: string) =>
		Axios.post<MessageResponse>("api/stories", {
			media,
			text: text || undefined,
		}),
	deleteStory: (id: string) =>
		Axios.delete<MessageResponse>(`api/stories/${id}`),
	markStorySeen: (id: string) =>
		Axios.post<MessageResponse>(`api/stories/seen/${id}`),
	muteStories: (username: string) =>
		Axios.put<MessageResponse>(`api/stories/mute/${username}`),
	/** Deletes a story upload that hasn't been posted yet. */
	deleteStoryUpload: (path: string) =>
		Axios.delete(`api/filepond/stories/${path.split("/").pop()}`),
	sendChat: (to: string, text: string, media?: string) =>
		Axios.post<MessageResponse & { data: ChatMessage }>("api/chats", {
			to,
			text,
			media: media || undefined,
		}),
	deleteChat: (id: string) =>
		Axios.delete<MessageResponse>(`api/chats/${id}`),
	saveSearch: (keyword: string) => Axios.post("api/search", { keyword }),
	deleteSearch: (id: string) =>
		Axios.delete<MessageResponse>(`api/search/${id}`),
	follow: (username: string) =>
		Axios.post<MessageResponse>("api/follows", { musician: username }),
	updateUser: (
		id: string,
		input: { bio?: string; withdrawal?: string; accountType?: "musician" }
	) => Axios.put<MessageResponse>(`api/users/${id}`, input),
}
