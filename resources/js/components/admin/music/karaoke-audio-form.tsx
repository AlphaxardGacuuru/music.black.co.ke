import { Mic2 } from "lucide-react"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select"
import { Spinner } from "@/components/ui/spinner"
import toast from "@/lib/toast"
import { commerceActions } from "@/queries/commerce"
import { apiErrorMessage } from "@/queries/feed"
import type { Audio } from "@/types/music"

/** Admin: pick an audio to offer as a karaoke backing track. */
export function KaraokeAudioForm({ audios }: { audios: Audio[] }) {
	const [audioId, setAudioId] = useState("")
	const [isSaving, setIsSaving] = useState(false)

	const onSave = () => {
		setIsSaving(true)

		commerceActions
			.setKaraokeAudio(audioId)
			.then((res) => {
				toast.success(res.data.message ?? "Karaoke audio set")
				setAudioId("")
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setIsSaving(false))
	}

	return (
		<div className="flex flex-col gap-3 sm:flex-row">
			<Select
				value={audioId}
				onValueChange={setAudioId}>
				<SelectTrigger
					className="w-full sm:flex-1"
					aria-label="Audio">
					<SelectValue placeholder="Select an audio" />
				</SelectTrigger>
				<SelectContent>
					{audios.map((audio) => (
						<SelectItem
							key={audio.id}
							value={audio.id}>
							{audio.name} ({audio.username})
						</SelectItem>
					))}
				</SelectContent>
			</Select>
			<Button
				className="gap-2"
				disabled={!audioId || isSaving}
				onClick={onSave}>
				{isSaving ? <Spinner /> : <Mic2 className="size-4" />}
				Set karaoke audio
			</Button>
		</div>
	)
}
