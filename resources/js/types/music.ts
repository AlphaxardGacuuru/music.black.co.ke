// Shapes returned by the music API resources (PostResource, StoryResource,
// VideoResource and UserResource for /api/artists).

export type Post = {
	id: string
	name: string
	username: string
	avatar: string
	decos: number
	text: string | null
	media: string | null
	parameter1: string | null
	parameter2: string | null
	parameter3: string | null
	parameter4: string | null
	parameter5: string | null
	hasVoted1: boolean
	hasVoted2: boolean
	hasVoted3: boolean
	hasVoted4: boolean
	hasVoted5: boolean
	percentage1: number
	percentage2: number
	percentage3: number
	percentage4: number
	percentage5: number
	winner: string
	totalVotes: number
	isWithin24Hrs: boolean
	hasMuted: boolean
	hasFollowed: boolean
	hasLiked: boolean
	hasEdited: boolean
	likes: number
	comments: number
	updatedAt: string
	createdAt: string
}

export type StoryMediaItem = { image?: string; video?: string }

export type Story = {
	id: string
	name: string
	username: string
	avatar: string
	media: StoryMediaItem[]
	text: string | null
	hasMuted: boolean
	hasSeen: boolean
}

export type Video = {
	id: string
	video: string
	name: string
	artistName: string
	username: string
	avatar: string
	artistDecos: number
	ft: string | null
	thumbnail: string
	hasLiked: boolean
	likes: number
	comments: number
	inCart: boolean
	hasBoughtVideo: boolean
	hasBought1: boolean
	hasFollowed: boolean
	downloads: number
	createdAt: string
}

export type Artist = {
	id: string
	name: string
	username: string
	avatar: string
	decos: number
	hasFollowed: boolean
	hasBought1: boolean
}

export type Paginated<T> = {
	data: T[]
	meta: { current_page: number; last_page: number; total: number }
}
