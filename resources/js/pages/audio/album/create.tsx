import { AlbumForm } from "@/components/audio/album-form"
import { MusicianPrompt } from "@/components/audio/musician-prompt"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"

/** Create an audio album (music.black's /audio/album/create). */
export default function AudioAlbumCreate() {
	const { auth } = useApp()

	return (
		<div className="py-2 md:py-4">
			<Head title="Create audio album | Black Music" />
			{auth?.accountType === "musician" ? <AlbumForm /> : <MusicianPrompt />}
		</div>
	)
}
