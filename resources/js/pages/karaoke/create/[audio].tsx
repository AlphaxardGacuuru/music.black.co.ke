import { MicVocal } from "lucide-react"
import { KaraokeRecorder } from "@/components/karaoke/karaoke-recorder"
import { Link } from "@/components/ui/link"
import { Head } from "@/lib/spa"
import { useBackingTrack } from "@/queries/karaoke"

/** Record a karaoke over a song; `audio` is the song's (Audio) id. */
export default function KaraokeCreate({ audio }: { audio: string }) {
	const track = useBackingTrack(audio)

	return (
		<>
			<Head
				title={
					track.data ? `Sing ${track.data.name} | Black Music` : "Sing | Black Music"
				}
			/>

			{track.isPending && (
				<div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-3 bg-black text-sm text-white/70">
					<span className="size-8 animate-spin rounded-full border-2 border-white/30 border-t-primary" />
					Loading the song...
				</div>
			)}

			{track.isError && (
				<div className="fixed inset-0 z-50 flex flex-col items-center justify-center gap-4 bg-black p-6 text-center text-white">
					<span className="flex size-14 items-center justify-center rounded-full bg-primary/15 text-primary">
						<MicVocal className="size-7" />
					</span>
					<p className="text-sm text-white/70">
						We couldn't find this song. Pick another one to sing.
					</p>
					<Link
						href="/karaoke/charts"
						variant="solid">
						Back to karaoke
					</Link>
				</div>
			)}

			{track.data && <KaraokeRecorder track={track.data} />}
		</>
	)
}
