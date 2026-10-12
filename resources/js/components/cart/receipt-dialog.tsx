import { PartyPopper } from "lucide-react"
import { DownloadButton } from "@/components/cart/download-button"
import { SongRow } from "@/components/cart/song-row"
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/components/ui/dialog"
import { Link } from "@/components/ui/link"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import type { Audio } from "@/types/music"

/** The bits of a song a receipt line needs. */
export type ReceiptVideo = {
	id: string
	name: string
	username: string
	ft: string | null
	thumbnail: string
}

export type Receipt = {
	videos: ReceiptVideo[]
	audios: Audio[]
}

type Props = {
	receipt: Receipt
	open: boolean
	onOpenChange: (open: boolean) => void
}

/** "Purchase successful" sheet listing what a payment just bought. */
export function ReceiptDialog({ receipt, open, onOpenChange }: Props) {
	const player = useAudioPlayer()

	return (
		<Dialog
			open={open}
			onOpenChange={onOpenChange}>
			<DialogContent className="max-h-[85vh] overflow-y-auto">
				<DialogHeader>
					<div className="mx-auto mb-1 flex size-12 items-center justify-center rounded-full bg-primary/15 text-primary sm:mx-0">
						<PartyPopper className="size-6" />
					</div>
					<DialogTitle>Purchase successful</DialogTitle>
					<DialogDescription>
						Congratulations! These songs are now yours to stream and download.
					</DialogDescription>
				</DialogHeader>

				{receipt.videos.length > 0 && (
					<section className="space-y-3">
						<h3 className="text-sm font-semibold">Videos</h3>
						{receipt.videos.map((video) => (
							<SongRow
								key={video.id}
								kind="video"
								{...video}>
								<DownloadButton
									kind="video"
									id={video.id}
								/>
							</SongRow>
						))}
					</section>
				)}

				{receipt.audios.length > 0 && (
					<section className="space-y-3">
						<h3 className="text-sm font-semibold">Audios</h3>
						{receipt.audios.map((audio) => (
							<SongRow
								key={audio.id}
								kind="audio"
								{...audio}
								isPlaying={player.current?.id === audio.id && player.isPlaying}
								onPlay={() =>
									player.current?.id === audio.id
										? player.toggle()
										: player.play(audio, receipt.audios)
								}>
								<DownloadButton
									kind="audio"
									id={audio.id}
								/>
							</SongRow>
						))}
					</section>
				)}

				<DialogFooter>
					<Link
						href="/library"
						variant="solid">
						Go to library
					</Link>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	)
}
