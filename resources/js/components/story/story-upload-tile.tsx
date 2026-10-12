import { X } from "lucide-react"
import { Button } from "@/components/ui/button"
import type { StoryUpload } from "@/types/social"

type Props = {
	/** The JSON string the story upload endpoint returned. */
	upload: string
	index: number
	onRemove: () => void
}

/** A 9:16 preview of one uploaded story segment with a remove button. */
export function StoryUploadTile({ upload, index, onRemove }: Props) {
	const parsed = JSON.parse(upload) as StoryUpload

	return (
		<div className="relative aspect-[9/16] w-24 shrink-0 overflow-hidden rounded-xl border border-border/60 bg-muted">
			{parsed.image && (
				<img
					src={`/storage/${parsed.image}`}
					alt={`Segment ${index + 1}`}
					className="size-full object-cover"
				/>
			)}
			{parsed.video && (
				<video
					src={`/storage/${parsed.video}`}
					muted
					playsInline
					preload="metadata"
					className="size-full object-cover"
				/>
			)}
			<span className="absolute bottom-1 left-1 rounded-full bg-black/60 px-1.5 text-[11px] font-semibold text-white">
				{index + 1}
			</span>
			<Button
				type="button"
				variant="secondary"
				size="icon"
				onClick={onRemove}
				aria-label={`Remove segment ${index + 1}`}
				className="absolute right-1 top-1 size-6 bg-black/60 text-white hover:bg-black/80">
				<X className="size-3.5" />
			</Button>
		</div>
	)
}
