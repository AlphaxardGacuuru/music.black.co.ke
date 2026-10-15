import { MusicianGate } from "@/components/video/musician-gate"
import { VideoForm } from "@/components/video/video-form"
import { Head } from "@/lib/spa"

export default function VideoCreate() {
	return (
		<MusicianGate>
			<Head title="Upload video | Black Music" />
			<div className="mx-auto w-full max-w-2xl space-y-6">
				<header>
					<p className="text-sm text-muted-foreground">Studio</p>
					<h1 className="text-2xl font-bold">Upload your video</h1>
					<p className="text-sm text-muted-foreground">It's free.</p>
				</header>
				<VideoForm mode="create" />
			</div>
		</MusicianGate>
	)
}
