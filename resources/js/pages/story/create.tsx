import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import { useState } from "react"
import { StoryUploadTile } from "@/components/story/story-upload-tile"
import { FileUpload } from "@/components/music/file-upload"
import { PageHeader } from "@/components/social/page-header"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Spinner } from "@/components/ui/spinner"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { apiErrorMessage, feedKeys } from "@/queries/feed"
import { socialActions } from "@/queries/social"
import type { StoryUpload } from "@/types/social"

const MAX_SEGMENTS = 10

/**
 * Upload one or more images or short videos (each becomes a segment of the
 * story) and an optional caption.
 */
export default function StoryCreate() {
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const [uploads, setUploads] = useState<string[]>([])
	const [text, setText] = useState("")
	const [uploadKey, setUploadKey] = useState(0)
	const [posting, setPosting] = useState(false)

	const onUploaded = (result: string) => {
		setUploads((current) => [...current, result])
		// A fresh drop zone for the next segment, once FilePond has finished
		window.setTimeout(() => setUploadKey((key) => key + 1), 800)
	}

	const onRemove = (index: number) => {
		const upload = uploads[index]
		const parsed = JSON.parse(upload) as StoryUpload
		const path = parsed.image ?? parsed.video

		setUploads(uploads.filter((_, i) => i !== index))

		if (path) {
			socialActions.deleteStoryUpload(path).catch(() => undefined)
		}
	}

	const onPost = () => {
		setPosting(true)

		socialActions
			.createStory(uploads.join(","), text.trim())
			.then((res) => {
				toast.success(res.data.message ?? "Story posted")
				void queryClient.invalidateQueries({ queryKey: feedKeys.stories })
				void navigate({ to: "/" })
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setPosting(false))
	}

	return (
		<>
			<Head title="Create story | Black Music" />
			<div className="mx-auto w-full max-w-xl space-y-4">
				<PageHeader title="Create story" />

				<GlassCard className="space-y-4 p-4 sm:p-5">
					{uploads.length > 0 && (
						<div className="-mx-1 flex gap-3 overflow-x-auto px-1 pb-1">
							{uploads.map((upload, index) => (
								<StoryUploadTile
									key={upload}
									upload={upload}
									index={index}
									onRemove={() => onRemove(index)}
								/>
							))}
						</div>
					)}

					{uploads.length < MAX_SEGMENTS && (
						<FileUpload
							key={uploadKey}
							endpoint="stories"
							field="filepond-media"
							acceptedFileTypes={["image/*", "video/*"]}
							maxFileSize="50MB"
							aspectRatio="9:16"
							className="mx-auto w-full max-w-64"
							label={
								uploads.length
									? 'Add another image or video, or <span class="filepond--label-action">browse</span>'
									: 'Drop an image or video, or <span class="filepond--label-action">browse</span>'
							}
							onUploaded={onUploaded}
						/>
					)}

					<textarea
						value={text}
						onChange={(event) => setText(event.target.value)}
						placeholder="Add a caption (optional)"
						rows={2}
						maxLength={500}
						className="w-full resize-none rounded-2xl border border-border bg-background/60 px-4 py-2.5 text-[15px] outline-none placeholder:text-muted-foreground focus:border-primary/60 focus:ring-2 focus:ring-primary/20"
					/>

					<div className="flex items-center justify-between gap-3">
						<p className="text-xs text-muted-foreground">
							{uploads.length
								? `${uploads.length} segment${uploads.length > 1 ? "s" : ""}`
								: "Upload at least one image or video."}
						</p>
						<Button
							onClick={onPost}
							disabled={uploads.length === 0 || posting}
							className="px-6">
							{posting ? <Spinner /> : "Post story"}
						</Button>
					</div>
				</GlassCard>
			</div>
		</>
	)
}
