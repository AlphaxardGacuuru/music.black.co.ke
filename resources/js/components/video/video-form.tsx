import { useQueryClient } from "@tanstack/react-query"
import { useNavigate } from "@tanstack/react-router"
import { Info } from "lucide-react"
import { useState, type FormEvent } from "react"
import { FileUpload } from "@/components/music/file-upload"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { SelectField, SelectItem } from "@/components/ui/select"
import { Spinner } from "@/components/ui/spinner"
import { Field, Textarea } from "@/components/video/form-fields"
import { useApp } from "@/contexts/AppContext"
import toast from "@/lib/toast"
import { apiErrorMessage } from "@/queries/feed"
import {
	toDateInput,
	useArtistVideoAlbums,
	videoActions,
	videoKeys,
	type VideoPayload,
} from "@/queries/video"
import type { VideoDetail } from "@/types/video"
import { VIDEO_ARTIST_SHARE, VIDEO_GENRES, VIDEO_PRICE } from "@/types/video"

type Props =
	| { mode: "create"; video?: undefined }
	| { mode: "edit"; video: VideoDetail }

/**
 * Upload or edit a video. When creating, the files are uploaded first and
 * saved with the form; when editing, a new file replaces the old one as soon
 * as it finishes uploading.
 */
export function VideoForm({ mode, video }: Props) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const albums = useArtistVideoAlbums(video?.username ?? auth?.username)

	const [name, setName] = useState(video?.name ?? "")
	const [ft, setFt] = useState(video?.ft ?? "")
	const [albumId, setAlbumId] = useState(video?.videoAlbumId ?? "")
	const [genre, setGenre] = useState(video?.genre ?? "")
	const [released, setReleased] = useState(toDateInput(video?.released))
	const [description, setDescription] = useState(video?.description ?? "")
	const [videoPath, setVideoPath] = useState("")
	const [thumbnailPath, setThumbnailPath] = useState("")
	const [saving, setSaving] = useState(false)

	const isCreate = mode === "create"

	const refresh = () => {
		void queryClient.invalidateQueries({ queryKey: videoKeys.all })
	}

	const onReplaced = (what: string) => {
		toast.success(`${what} replaced`)
		refresh()
	}

	const onSubmit = (event: FormEvent) => {
		event.preventDefault()

		if (isCreate && !videoPath) {
			toast.error("Upload your video first")

			return
		}

		if (isCreate && (!albumId || !genre)) {
			toast.error("Pick an album and a genre")

			return
		}

		const payload: VideoPayload = {
			name: name.trim(),
			ft: ft.trim() || undefined,
			video_album_id: albumId || undefined,
			genre: genre || undefined,
			released: released || undefined,
			description: description.trim() || undefined,
		}

		if (isCreate) {
			payload.video = videoPath
			payload.thumbnail = thumbnailPath || undefined
		}

		setSaving(true)

		const request = isCreate
			? videoActions.create(payload)
			: videoActions.update(video.id, payload)

		request
			.then((res) => {
				toast.success(res.data.message ?? "Saved")
				refresh()

				if (isCreate) {
					void navigate({ to: "/video" as never })
				}
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setSaving(false))
	}

	return (
		<form
			onSubmit={onSubmit}
			className="space-y-5">
			<GlassCard className="space-y-5 p-4 sm:p-6">
				<Input
					label="Video name"
					value={name}
					onChange={(event) => setName(event.target.value)}
					required={isCreate}
				/>

				<Input
					label="Featuring artist (username)"
					value={ft}
					onChange={(event) => setFt(event.target.value)}
					helperText="Optional, e.g. @JohnDoe. They must have a Black Music account."
				/>

				<div className="grid gap-5 sm:grid-cols-2">
					<SelectField
						label="Album"
						placeholder={albums.isPending ? "Loading albums" : "Select album"}
						value={albumId}
						onValueChange={setAlbumId}
						helperText={
							albums.data?.length === 0 ? "Create an album first." : undefined
						}>
						{albums.data?.map((album) => (
							<SelectItem
								key={album.id}
								value={album.id}>
								{album.name}
							</SelectItem>
						))}
					</SelectField>

					<SelectField
						label="Genre"
						placeholder="Select genre"
						value={genre}
						onValueChange={setGenre}>
						{VIDEO_GENRES.map((item) => (
							<SelectItem
								key={item}
								value={item}>
								{item}
							</SelectItem>
						))}
					</SelectField>
				</div>

				<Input
					type="date"
					label="Released"
					value={released}
					onChange={(event) => setReleased(event.target.value)}
					required={isCreate}
				/>

				<Field label="Description">
					<Textarea
						placeholder="Say something about your song"
						value={description}
						onChange={(event) => setDescription(event.target.value)}
						required={isCreate}
						rows={5}
					/>
				</Field>
			</GlassCard>

			<GlassCard className="space-y-5 p-4 sm:p-6">
				<Field
					label={isCreate ? "Video" : "Replace video"}
					hint={
						isCreate
							? "Up to 200MB. If your video is bigger, compress it first (for example by uploading it to YouTube and downloading it again)."
							: "The new file replaces the current video as soon as it finishes uploading."
					}>
					<FileUpload
						endpoint={isCreate ? "video" : `video/${video.id}`}
						field="filepond-video"
						acceptedFileTypes={["video/*"]}
						maxFileSize="200MB"
						label='Drag and drop your video or <span class="filepond--label-action">browse</span>'
						onUploaded={(path) =>
							isCreate ? setVideoPath(path) : onReplaced("Video")
						}
						onRemoved={() => setVideoPath("")}
					/>
				</Field>

				<Field
					label={isCreate ? "Thumbnail (optional)" : "Replace thumbnail"}
					hint={
						isCreate
							? "Skip this and we'll make a thumbnail from a frame of your video."
							: "The new image replaces the current thumbnail as soon as it finishes uploading."
					}>
					<FileUpload
						endpoint={isCreate ? "video-thumbnail" : `video-thumbnail/${video.id}`}
						field="filepond-thumbnail"
						acceptedFileTypes={["image/*"]}
						maxFileSize="10MB"
						label='Drag and drop an image or <span class="filepond--label-action">browse</span>'
						onUploaded={(path) =>
							isCreate ? setThumbnailPath(path) : onReplaced("Thumbnail")
						}
						onRemoved={() => setThumbnailPath("")}
					/>
				</Field>
			</GlassCard>

			{isCreate && (
				<div className="flex gap-3 rounded-2xl border border-primary/30 bg-primary/10 p-4 text-sm">
					<Info className="mt-0.5 size-4 shrink-0 text-primary" />
					<p>
						By uploading you confirm that you own this song. Videos sell for{" "}
						<b>KES {VIDEO_PRICE}</b>: you get{" "}
						<b>KES {VIDEO_ARTIST_SHARE}</b> (50%) of every sale and Black Music
						keeps the other half.
					</p>
				</div>
			)}

			<div className="flex flex-wrap items-center gap-3">
				<Button
					type="submit"
					size="lg"
					disabled={saving}>
					{saving && <Spinner />}
					{isCreate ? "Upload video" : "Save changes"}
				</Button>
				<Link
					href="/video"
					variant="ghost"
					size="lg">
					Back to studio
				</Link>
			</div>
		</form>
	)
}
