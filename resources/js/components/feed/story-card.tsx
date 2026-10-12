import { FeedImage } from "@/components/feed/feed-image"
import { Link } from "@/components/ui/link"
import { cn } from "@/lib/utils"
import type { Story } from "@/types/music"

/** A tall story thumbnail with a gold ring until it has been seen. */
export function StoryCard({ story }: { story: Story }) {
	const cover = story.media[0]

	return (
		<Link
			href={`/story/show/${story.id}`}
			variant="unstyled"
			className="group w-28 shrink-0 snap-start sm:w-32">
			<div
				className={cn(
					"relative aspect-[9/16] overflow-hidden rounded-2xl border-2 bg-muted",
					story.hasSeen ? "border-border" : "border-primary"
				)}>
				{cover?.image && (
					<FeedImage
						src={`/storage/${cover.image}`}
						className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
					/>
				)}
				{!cover?.image && cover?.video && (
					<video
						src={`/storage/${cover.video}`}
						muted
						playsInline
						preload="metadata"
						className="size-full object-cover"
					/>
				)}
				<div className="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/80 to-transparent p-2 pt-8">
					<span className="mb-1 block size-7 overflow-hidden rounded-full border-2 border-white/80 bg-primary/40">
						<FeedImage
							src={story.avatar}
							className="size-full object-cover"
						/>
					</span>
					<p className="truncate text-xs font-semibold text-white">
						{story.name}
					</p>
					<p className="truncate text-[11px] text-white/70">{story.username}</p>
				</div>
			</div>
		</Link>
	)
}
