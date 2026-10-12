import { AlbumForm } from "@/components/video/album-form"
import { MusicianGate } from "@/components/video/musician-gate"
import { Head } from "@/lib/spa"

export default function VideoAlbumCreate() {
	return (
		<MusicianGate>
			<Head title="Create video album | Black Music" />
			<div className="mx-auto w-full max-w-2xl space-y-6 px-4 py-6">
				<header>
					<p className="text-sm text-muted-foreground">Studio</p>
					<h1 className="text-2xl font-bold">Create video album</h1>
				</header>
				<AlbumForm mode="create" />
			</div>
		</MusicianGate>
	)
}
