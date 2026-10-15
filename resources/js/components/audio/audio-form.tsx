import { useQueryClient } from "@tanstack/react-query"
import { useNavigate } from "@tanstack/react-router"
import { useState, type FormEvent } from "react"
import { FileUpload } from "@/components/music/file-upload"
import { Button } from "@/components/ui/button"
import { GlassCard, GlassInner } from "@/components/ui/glass-card"
import { Input } from "@/components/ui/input"
import { Link } from "@/components/ui/link"
import { SelectField, SelectItem } from "@/components/ui/select"
import { useApp } from "@/contexts/AppContext"
import toast from "@/lib/toast"
import {
	AUDIO_ARTIST_SHARE,
	AUDIO_PRICE,
	GENRES,
	audioActions,
	audioKeys,
	useArtistAudioAlbums,
} from "@/queries/audio"
import { apiErrorMessage } from "@/queries/feed"
import type { AudioForm as AudioFormFields } from "@/types/audio"
import type { Audio } from "@/types/music"
import { ArrowLeft, Upload } from "lucide-react"
import { Spinner } from "../ui/spinner"

const MONTHS = [
	"Jan",
	"Feb",
	"Mar",
	"Apr",
	"May",
	"Jun",
	"Jul",
	"Aug",
	"Sep",
	"Oct",
	"Nov",
	"Dec",
]

/** The API sends dates as "05 Mar 2024"; date inputs want "2024-03-05". */
export function toDateInput(value: string | null | undefined): string {
	const match = value?.match(/^(\d{1,2}) ([A-Za-z]{3}) (\d{4})$/)

	if (!match) {
		return ""
	}

	const month = MONTHS.indexOf(match[2]) + 1

	if (month === 0) {
		return ""
	}

	return `${match[3]}-${String(month).padStart(2, "0")}-${match[1].padStart(2, "0")}`
}

export const textareaClass =
	"min-h-28 w-full resize-y rounded-lg border border-neutral-300 bg-transparent px-4 py-3 text-base font-light outline-none placeholder:text-neutral-500 focus:border-neutral-500 focus:ring-2 focus:ring-neutral-400/20 dark:border-white/20 dark:placeholder:text-white/50 dark:focus:border-white/40"

/**
 * Upload or edit a song (music.black's /audio/create and /audio/edit/:id).
 * When editing, a new thumbnail or audio file replaces the old one as soon
 * as it finishes uploading.
 */
export function AudioForm({ audio }: { audio?: Audio }) {
	const { auth } = useApp()
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const albums = useArtistAudioAlbums(auth?.username)
	const isEditing = Boolean(audio)

	const [name, setName] = useState(audio?.name ?? "")
	const [ft, setFt] = useState(audio?.ft ?? "")
	const [albumId, setAlbumId] = useState(audio?.audioAlbumId ?? "")
	const [genre, setGenre] = useState(audio?.genre ?? "")
	const [released, setReleased] = useState(toDateInput(audio?.released))
	const [description, setDescription] = useState(audio?.description ?? "")
	const [thumbnail, setThumbnail] = useState("")
	const [file, setFile] = useState("")
	const [agreed, setAgreed] = useState(isEditing)
	const [saving, setSaving] = useState(false)

	const refresh = () =>
		queryClient.invalidateQueries({ queryKey: audioKeys.all })

	const onSubmit = (event: FormEvent) => {
		event.preventDefault()

		if (!isEditing && (!thumbnail || !file)) {
			toast.error("Upload the thumbnail and the audio first.")

			return
		}

		if (!albumId || !genre) {
			toast.error("Pick an album and a genre.")

			return
		}

		const form: AudioFormFields = {
			name: name.trim(),
			ft: ft.trim() || undefined,
			audio_album_id: albumId,
			genre,
			released: released || undefined,
			description: description.trim() || undefined,
		}

		setSaving(true)

		const request = audio
			? audioActions.update(audio.id, form)
			: audioActions.create({ ...form, audio: file, thumbnail })

		request
			.then((res) => {
				toast.success(
					res.data.message ?? (audio ? "Audio edited" : "Audio uploaded")
				)
				void refresh()
				void navigate({ to: "/audios" })
			})
			.catch((error: unknown) => toast.error(apiErrorMessage(error)))
			.finally(() => setSaving(false))
	}

	const onReplaced = (what: string) => {
		toast.success(`${what} updated`)
		void refresh()
	}

	return (
		<GlassCard className="mx-auto w-full max-w-2xl p-5 md:p-8">
			<p className="text-xs font-medium uppercase tracking-widest text-primary">
				Studio
			</p>
			<h1 className="text-2xl font-bold tracking-tight">
				{isEditing ? `Edit ${audio?.name}` : "Upload your audio"}
			</h1>
			{!isEditing && (
				<p className="text-sm text-muted-foreground">It's free.</p>
			)}

			<form
				onSubmit={onSubmit}
				className="mt-6 space-y-4">
				<Input
					label="Audio name"
					value={name}
					onChange={(event) => setName(event.target.value)}
					required
				/>

				<Input
					label="Featuring artist, e.g. @JohnDoe"
					value={ft}
					onChange={(event) => setFt(event.target.value)}
					helperText="They must have a Black Music account."
				/>

				<SelectField
					label="Album"
					value={albumId}
					onValueChange={setAlbumId}
					disabled={albums.isPending}>
					{(albums.data ?? []).map((album) => (
						<SelectItem
							key={album.id}
							value={album.id}>
							{album.name}
						</SelectItem>
					))}
				</SelectField>

				<SelectField
					label="Genre"
					value={genre}
					onValueChange={setGenre}>
					{GENRES.map((item) => (
						<SelectItem
							key={item}
							value={item}>
							{item}
						</SelectItem>
					))}
				</SelectField>

				<Input
					type="date"
					label="Released"
					value={released}
					onChange={(event) => setReleased(event.target.value)}
					required={!isEditing}
				/>

				<textarea
					value={description}
					onChange={(event) => setDescription(event.target.value)}
					placeholder="Say something about your song"
					rows={5}
					required={!isEditing}
					className={textareaClass}
				/>

				<div className="space-y-2">
					<p className="text-sm font-medium">
						{isEditing ? "Replace the audio" : "Audio file"}
					</p>
					<p className="text-xs text-muted-foreground">
						If the file is too large, upload it to YouTube for compression,
						download it, delete it, then upload it here.
					</p>
					<FileUpload
						endpoint={audio ? `audio/${audio.id}` : "audio"}
						field="filepond-audio"
						acceptedFileTypes={["audio/*"]}
						maxFileSize="200MB"
						onUploaded={(path) => (audio ? onReplaced("Audio") : setFile(path))}
						onRemoved={() => setFile("")}
						label='Drop the song or <span class="filepond--label-action">browse</span>'
					/>
				</div>

				<div className="space-y-2">
					<p className="text-sm font-medium">
						{isEditing ? "Replace the thumbnail" : "Audio thumbnail"}
					</p>
					<FileUpload
						endpoint={audio ? `audio-thumbnail/${audio.id}` : "audio-thumbnail"}
						field="filepond-thumbnail"
						acceptedFileTypes={["image/*"]}
						maxFileSize="10MB"
						onUploaded={(path) =>
							audio ? onReplaced("Thumbnail") : setThumbnail(path)
						}
						onRemoved={() => setThumbnail("")}
						label='Drop the cover image or <span class="filepond--label-action">browse</span>'
					/>
				</div>

				{!isEditing && (
					<GlassInner className="space-y-2 p-4 text-sm">
						<p className="font-semibold">Before you upload</p>
						<p className="text-muted-foreground">
							By uploading you agree that you{" "}
							<b className="text-foreground">own</b> this song. Audios sell at{" "}
							<b className="text-primary">KES {AUDIO_PRICE}</b>: Black Music
							takes 50% (KES {AUDIO_PRICE - AUDIO_ARTIST_SHARE}) and you take
							50% (KES {AUDIO_ARTIST_SHARE}).
						</p>
						<label className="flex cursor-pointer items-center gap-2 pt-1">
							<input
								type="checkbox"
								checked={agreed}
								onChange={(event) => setAgreed(event.target.checked)}
								className="size-4 accent-primary"
							/>
							I own this song
						</label>
					</GlassInner>
				)}

				<div className="flex flex-wrap items-center justify-between gap-3 pt-2">
					<Link
						href="/audios"
						variant="outline"
						size="lg">
						<ArrowLeft className="mr-2" />
						Back to studio
					</Link>
					<Button
						type="submit"
						disabled={saving || !agreed}
						size="lg">
						{saving ? <Spinner /> : <Upload className="mr-2" />}
						{saving ? "Saving..." : isEditing ? "Save changes" : "Upload audio"}
					</Button>
				</div>
			</form>
		</GlassCard>
	)
}
