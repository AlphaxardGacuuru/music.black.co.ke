import { useInfiniteQuery } from "@tanstack/react-query"
import Axios from "@/lib/axios"
import type { CommentKind, MusicComment, Paginated } from "@/types/music"

export const commentKeys = {
	list: (kind: CommentKind, parentId: string) =>
		["comments", kind, parentId] as const,
}

/** A video's, audio's, karaoke's or post's comments, newest first, 10 a page. */
export function useComments(kind: CommentKind, parentId: string) {
	return useInfiniteQuery({
		queryKey: commentKeys.list(kind, parentId),
		initialPageParam: 1,
		queryFn: ({ pageParam }) =>
			Axios.get<Paginated<MusicComment>>(`api/${kind}-comments/${parentId}`, {
				params: { page: pageParam },
			}).then((res) => res.data),
		getNextPageParam: (lastPage) =>
			lastPage.meta.current_page < lastPage.meta.last_page
				? lastPage.meta.current_page + 1
				: undefined,
	})
}

type MessageResponse = { message?: string }

export const commentActions = {
	add: (kind: CommentKind, parentId: string, text: string) =>
		Axios.post<MessageResponse>(`api/${kind}-comments`, { id: parentId, text }),
	like: (kind: CommentKind, commentId: string) =>
		Axios.post<MessageResponse>(`api/${kind}-comment-likes`, {
			comment: commentId,
		}),
	remove: (kind: CommentKind, commentId: string) =>
		Axios.delete<MessageResponse>(`api/${kind}-comments/${commentId}`),
}
