import { useQueryClient } from "@tanstack/react-query"
import { useNavigate } from "@tanstack/react-router"
import { ImagePlus } from "lucide-react"
import { useEffect, useRef, useState, type FormEvent } from "react"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { Spinner } from "@/components/ui/spinner"
import { Field } from "@/components/video/form-fields"
import { mediaUrl } from "@/lib/media"
import toast from "@/lib/toast"
import { apiErrorMessage } from "@/queries/feed"
import { toDateInput, videoActions, videoKeys } from "@/queries/video"
import type { VideoAlbum } from "@/types/video"

/** VideoAlbumController accepts covers up to 1999 KB. */
const MAX_COVER_BYTES = 1999 * 1024

type Props =
	| { mode: "create"; album?: undefined }
	| { mode: "edit"; album: VideoAlbum }

/** Create or edit a video album: name, release date and cover image. */
export function AlbumForm({ mode, album }: Props) {
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const fileInput = useRef<HTMLInputElement>(null)

	const [name, setName] = useState(album?.name ?? "")
	const [released, setReleased] = useState(toDateInput(album?.released))
	const [cover, setCover] = useState<File | null>(null)
	const [preview, setPreview] = useState<string | null>(null)
	const [saving, setSaving] = useState(false)

	const isCreate = mode === "create"

	useEffect(() => {
		if (!cover) {
			setPreview(null)

			return
		}

		const url = URL.createObjectURL(cover)
		setPreview(url)

		return () => URL.revokeObjectURL(url)
	}, [cover])

	const onPick = (file: File | undefined) => {
		if (!file) {
			return
		}

		if (!file.type.startsWith("image/")) {
			toast.error("Pick an image file")

			return
		}

		if (file.size > MAX_COVER_BYTES) {
			toast.error("The cover must be smaller than 2MB")

			return
		}

		setCover(file)
	}

	const onSubmit = (event: FormEvent) => {
		event.preventDefault()

		if (isCreate && !cover) {
			toast.error("Add a cover image")

			return
		}

		const data = new FormData()

		if (name.trim()) {
			data.append("name", name.trim())
		}

		if (released) {
			data.append("released", released)
		}

		if (cover) {
			data.append("cover", cover)
		}

		setSaving(true)

		const request = isCreate
			? videoActions.createAlbum(data)
			: videoActions.updateAlbum(album.id, data)

		request
			.then((res) => {
				toast.success(res.data.message ?? "Saved")
				void queryClient.invalidateQueries({ queryKey: videoKeys.all })

				if (isCreate) {
					void navigate({ to: "/videos" as never })
				} else {
					setCover(null)
				}
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setSaving(false))
	}

	const shownCover = preview ?? (album ? mediaUrl(album.cover) : null)

	return (
		<form
			onSubmit={onSubmit}
			className="space-y-5">
			<GlassCard className="space-y-5 p-4 sm:p-6">
				<Input
					label="Album name"
					value={name}
					onChange={(event) => setName(event.target.value)}
					required={isCreate}
				/>
				<Input
					type="date"
					label="Released"
					value={released}
					onChange={(event) => setReleased(event.target.value)}
					required={isCreate}
				/>

				<Field
					label={isCreate ? "Cover" : "Change cover"}
					hint="A square image works best, up to 2MB.">
					<button
						type="button"
						onClick={() => fileInput.current?.click()}
						className="group relative flex aspect-square w-full max-w-xs items-center justify-center overflow-hidden rounded-2xl border border-dashed border-border bg-muted/60 transition-colors hover:border-primary">
						{shownCover ? (
							<img
								src={shownCover}
								alt="Album cover"
								className="size-full object-cover"
							/>
						) : (
							<span className="flex flex-col items-center gap-2 text-sm text-muted-foreground">
								<ImagePlus className="size-8" />
								Choose a cover
							</span>
						)}
						{shownCover && (
							<span className="absolute inset-x-0 bottom-0 bg-black/60 py-2 text-xs font-medium text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
								Change cover
							</span>
						)}
					</button>
					<input
						ref={fileInput}
						type="file"
						accept="image/*"
						className="hidden"
						onChange={(event) => {
							onPick(event.target.files?.[0])
							event.target.value = ""
						}}
					/>
				</Field>
			</GlassCard>

			<div className="flex flex-wrap items-center gap-3">
				<Button
					type="submit"
					size="lg"
					disabled={saving}>
					{saving && <Spinner />}
					{isCreate ? "Create album" : "Save changes"}
				</Button>
				<Link
					href="/videos"
					variant="ghost"
					size="lg">
					Back to studio
				</Link>
			</div>
		</form>
	)
}
