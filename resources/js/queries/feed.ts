import { useInfiniteQuery, useQuery } from "@tanstack/react-query"
import { isAxiosError } from "axios"
import Axios from "@/lib/axios"
import type { Artist, Paginated, Post, Story, Video } from "@/types/music"

export const feedKeys = {
	posts: ["posts"] as const,
	stories: ["stories"] as const,
	videos: ["videos"] as const,
	artists: ["artists"] as const,
}

export function usePosts() {
	return useInfiniteQuery({
		queryKey: feedKeys.posts,
		initialPageParam: 1,
		queryFn: ({ pageParam }) =>
			Axios.get<Paginated<Post>>("api/posts", {
				params: { page: pageParam },
			}).then((res) => res.data),
		getNextPageParam: (lastPage) =>
			lastPage.meta.current_page < lastPage.meta.last_page
				? lastPage.meta.current_page + 1
				: undefined,
	})
}

export function useStories() {
	return useQuery({
		queryKey: feedKeys.stories,
		queryFn: () =>
			Axios.get<Paginated<Story>>("api/stories").then((res) => res.data.data),
	})
}

export function useVideos() {
	return useQuery({
		queryKey: feedKeys.videos,
		queryFn: () =>
			Axios.get<{ data: Video[] }>("api/videos").then((res) => res.data.data),
	})
}

export function useArtists() {
	return useQuery({
		queryKey: feedKeys.artists,
		queryFn: () =>
			Axios.get<Paginated<Artist>>("api/artists").then((res) => res.data.data),
	})
}

type MessageResponse = { message?: string }

export const feedActions = {
	likePost: (postId: string) =>
		Axios.post<MessageResponse>("api/post-likes", { post: postId }),
	vote: (postId: string, parameter: string) =>
		Axios.post<MessageResponse>("api/polls", { post: postId, parameter }),
	follow: (username: string) =>
		Axios.post<MessageResponse>("api/follows", { musician: username }),
	mutePosts: (username: string) =>
		Axios.put<MessageResponse>(`api/posts/mute/${username}`),
	deletePost: (postId: string) =>
		Axios.delete<MessageResponse>(`api/posts/${postId}`),
	cartVideo: (videoId: string) =>
		Axios.post<MessageResponse>("api/cart-videos", { video: videoId }),
}

/** Pull a readable message out of a failed API call. */
export function apiErrorMessage(error: unknown): string {
	if (isAxiosError(error)) {
		const data = error.response?.data as
			| { message?: string; errors?: Record<string, string[]> }
			| undefined
		const firstFieldError = data?.errors
			? Object.values(data.errors)[0]?.[0]
			: undefined

		return firstFieldError ?? data?.message ?? "Something went wrong."
	}

	return "Something went wrong."
}
