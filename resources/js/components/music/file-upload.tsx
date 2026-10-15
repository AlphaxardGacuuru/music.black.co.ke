import { FilePond, registerPlugin } from "react-filepond"
import type { FilePondServerConfigProps } from "filepond"
import FilePondPluginFileValidateSize from "filepond-plugin-file-validate-size"
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type"
import FilePondPluginImageExifOrientation from "filepond-plugin-image-exif-orientation"
import FilePondPluginImagePreview from "filepond-plugin-image-preview"
import "filepond/dist/filepond.min.css"
import "filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css"
import Axios from "@/lib/axios"
import { apiErrorMessage } from "@/queries/feed"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"

registerPlugin(
	FilePondPluginFileValidateSize,
	FilePondPluginFileValidateType,
	FilePondPluginImageExifOrientation,
	FilePondPluginImagePreview
)

type Props = {
	/**
	 * The upload endpoint under /api/filepond, e.g. "video", "audio-thumbnail",
	 * "posts" or "karaokes". To replace an existing song's file, include its id:
	 * "video/<videoId>" (those uploads can't be undone, the old file is gone).
	 */
	endpoint: string
	/** The form field the server reads, e.g. "filepond-video". */
	field: string
	/** Receives what the server stored, e.g. "videos/abc.mp4" (or {image: path} for stories). */
	onUploaded: (result: string) => void
	/** Called when the user removes an upload they just made. */
	onRemoved?: () => void
	acceptedFileTypes?: string[]
	maxFileSize?: string
	label?: string
	/** Fixed "width:height" shape for the drop zone, e.g. "9:16" for stories. */
	aspectRatio?: string
	className?: string
}

/**
 * A FilePond drop zone wired to the music API: uploads go through the app's
 * signed-in Axios client, and removing a just-uploaded file asks the server
 * to delete it again (it refuses once a song, post or story uses the file).
 */
export function FileUpload({
	endpoint,
	field,
	onUploaded,
	onRemoved,
	acceptedFileTypes,
	maxFileSize = "200MB",
	label = 'Drag and drop or <span class="filepond--label-action">browse</span>',
	aspectRatio,
	className,
}: Props) {
	const isReplacing = endpoint.includes("/")

	const server: FilePondServerConfigProps["server"] = {
		process: (_fieldName, file, _metadata, load, error, progress, abort) => {
			const controller = new AbortController()
			const data = new FormData()
			data.append(field, file, file.name)

			Axios.post(`api/filepond/${endpoint}`, data, {
				signal: controller.signal,
				onUploadProgress: (event) =>
					progress(true, event.loaded, event.total ?? file.size),
			})
				.then((res) => {
					const result =
						typeof res.data === "string" ? res.data : JSON.stringify(res.data)

					onUploaded(result)
					load(result)
				})
				.catch((err) => {
					const message = apiErrorMessage(err)
					toast.error(message)
					error(message)
				})

			return {
				abort: () => {
					controller.abort()
					abort()
				},
			}
		},
		revert: isReplacing
			? null
			: (uniqueFileId, load, error) => {
					// The server returned "folder/file.ext" (or a story's JSON);
					// the delete route takes just the file name.
					const path = uniqueFileId.match(/[^/"\\]+\.[A-Za-z0-9]+/)?.[0]

					if (!path) {
						load()
						onRemoved?.()

						return
					}

					Axios.delete(`api/filepond/${endpoint}/${path}`)
						.then(() => {
							onRemoved?.()
							load()
						})
						.catch((err) => error(apiErrorMessage(err)))
				},
	}

	return (
		<div className={cn("[&_.filepond--panel-root]:bg-muted/60", className)}>
			<FilePond
				name={field}
				server={server}
				allowMultiple={false}
				credits={false}
				acceptedFileTypes={acceptedFileTypes}
				maxFileSize={maxFileSize}
				labelIdle={label}
				stylePanelAspectRatio={aspectRatio}
			/>
		</div>
	)
}
