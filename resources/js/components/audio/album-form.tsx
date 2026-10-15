import { useQueryClient } from "@tanstack/react-query"
import { useNavigate } from "@tanstack/react-router"
import { ImagePlus } from "lucide-react"
import { useEffect, useRef, useState, type ChangeEvent, type FormEvent } from "react"
import { toDateInput } from "@/components/audio/audio-form"
import { FeedImage } from "@/components/feed/feed-image"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { mediaUrl } from "@/lib/media"
import toast from "@/lib/toast"
import { audioActions, audioKeys } from "@/queries/audio"
import { apiErrorMessage } from "@/queries/feed"
import type { AudioAlbum } from "@/types/audio"

/** The API rejects covers over 1999 KB. */
const MAX_COVER_BYTES = 1999 * 1024

/** Create or edit an audio album (music.black's /audio/album/create and edit). */
export function AlbumForm({ album }: { album?: AudioAlbum }) {
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const inputRef = useRef<HTMLInputElement>(null)

	const [name, setName] = useState(album?.name ?? "")
	const [released, setReleased] = useState(toDateInput(album?.released))
	const [cover, setCover] = useState<File | null>(null)
	const [preview, setPreview] = useState<string | null>(null)
	const [saving, setSaving] = useState(false)

	useEffect(() => {
		if (!preview) {
			return
		}

		return () => URL.revokeObjectURL(preview)
	}, [preview])

	const onPick = (event: ChangeEvent<HTMLInputElement>) => {
		const picked = event.target.files?.[0]

		if (!picked) {
			return
		}

		if (picked.size > MAX_COVER_BYTES) {
			toast.error("The cover must be under 2 MB.")
			event.target.value = ""

			return
		}

		setCover(picked)
		setPreview(URL.createObjectURL(picked))
	}

	const onSubmit = (event: FormEvent) => {
		event.preventDefault()

		if (!album && !cover) {
			toast.error("Pick a cover image.")

			return
		}

		setSaving(true)

		const form = { name: name.trim(), released, cover }
		const request = album
			? audioActions.updateAlbum(album.id, form)
			: audioActions.createAlbum(form)

		request
			.then((res) => {
				toast.success(res.data.message ?? "Album saved")
				void queryClient.invalidateQueries({ queryKey: audioKeys.all })
				void navigate({ to: "/audios" })
			})
			.catch((error: unknown) => toast.error(apiErrorMessage(error)))
			.finally(() => setSaving(false))
	}

	const coverSrc = preview ?? (album ? mediaUrl(album.cover) : null)

	return (
		<GlassCard className="mx-auto w-full max-w-lg p-5 md:p-8">
			<p className="text-xs font-medium uppercase tracking-widest text-primary">Studio</p>
			<h1 className="text-2xl font-bold tracking-tight">
				{album ? `Edit ${album.name}` : "Create audio album"}
			</h1>

			<form
				onSubmit={onSubmit}
				className="mt-6 space-y-4">
				<button
					type="button"
					onClick={() => inputRef.current?.click()}
					className="group relative mx-auto flex aspect-square w-full max-w-60 cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border border-dashed border-border bg-muted/50 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary">
					<ImagePlus className="size-8" />
					{album ? "Change cover" : "Choose cover"}
					{coverSrc && (
						<FeedImage
							key={coverSrc}
							src={coverSrc}
							alt="Album cover"
							className="absolute inset-0 size-full object-cover transition-opacity group-hover:opacity-60"
						/>
					)}
				</button>
				<input
					ref={inputRef}
					type="file"
					accept="image/*"
					className="hidden"
					onChange={onPick}
				/>

				<Input
					label="Album name"
					value={name}
					onChange={(event) => setName(event.target.value)}
					required={!album}
				/>

				<Input
					type="date"
					label="Released"
					value={released}
					onChange={(event) => setReleased(event.target.value)}
					required={!album}
				/>

				<div className="flex flex-wrap items-center justify-between gap-3 pt-2">
					<Link
						href="/audios"
						variant="muted"
						size="none">
						Back to studio
					</Link>
					<Button
						type="submit"
						disabled={saving}>
						{saving ? "Saving..." : album ? "Save changes" : "Create album"}
					</Button>
				</div>
			</form>
		</GlassCard>
	)
}
