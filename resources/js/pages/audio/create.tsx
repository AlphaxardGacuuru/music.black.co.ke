import { AudioForm } from "@/components/audio/audio-form"
import { MusicianPrompt } from "@/components/audio/musician-prompt"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"

/** Upload a song (music.black's /audio/create). */
export default function AudioCreate() {
	const { auth } = useApp()

	return (
		<div className="py-2 md:py-4">
			<Head title="Upload audio | Black Music" />
			{auth?.accountType === "musician" ? <AudioForm /> : <MusicianPrompt />}
		</div>
	)
}
