import { Lock, RotateCcw } from "lucide-react"
import { useRef, useState } from "react"
import { Button } from "@/components/ui/button"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { mediaUrl } from "@/lib/media"
import { videoSource } from "@/queries/video"
import { VIDEO_PRICE } from "@/types/video"

/** Non-buyers watch from second 1 to second 10, as on music.black. */
const PREVIEW_START = 1
const PREVIEW_END = 10

type Props = {
	path: string
	thumbnail: string | null
	name: string
	/** Buyers (and the artist) watch the whole video with full controls. */
	fullAccess: boolean
	onBuy: () => void
}

/**
 * The video page player. Uploaded videos play in a <video>; OLD videos saved
 * as YouTube links play in an iframe. Without full access only a 10 second
 * preview plays, then a buy prompt covers the player.
 */
export function VideoPlayer({ path, thumbnail, name, fullAccess, onBuy }: Props) {
	const player = useAudioPlayer()
	const videoRef = useRef<HTMLVideoElement>(null)
	const [previewOver, setPreviewOver] = useState(false)
	const { src, isLink } = videoSource(path)

	// Stop the app's song when a video starts.
	const onPlay = () => {
		if (player.isPlaying) {
			player.toggle()
		}
	}

	const onTimeUpdate = () => {
		const element = videoRef.current

		if (fullAccess || !element) {
			return
		}

		if (element.currentTime >= PREVIEW_END) {
			element.pause()
			element.currentTime = PREVIEW_END
			setPreviewOver(true)
		}
	}

	const onReplay = () => {
		const element = videoRef.current

		if (!element) {
			return
		}

		setPreviewOver(false)
		element.currentTime = PREVIEW_START
		void element.play()
	}

	return (
		<div className="relative aspect-video w-full overflow-hidden rounded-2xl border border-border/60 bg-black sm:rounded-3xl">
			{isLink ? (
				<iframe
					key={src}
					title={name}
					className="size-full"
					src={
						fullAccess
							? `${src}?autoplay=1`
							: `${src}?autoplay=1&end=${PREVIEW_END}&controls=0`
					}
					allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
					allowFullScreen
				/>
			) : (
				<video
					key={src}
					ref={videoRef}
					className="size-full"
					src={fullAccess ? src : `${src}#t=${PREVIEW_START},${PREVIEW_END}`}
					poster={mediaUrl(thumbnail) || undefined}
					controls
					controlsList="nodownload"
					disablePictureInPicture={!fullAccess}
					playsInline
					autoPlay
					onPlay={onPlay}
					onTimeUpdate={onTimeUpdate}
					onSeeking={onTimeUpdate}
					onContextMenu={(event) => event.preventDefault()}
				/>
			)}

			{!fullAccess && (
				<span className="pointer-events-none absolute left-3 top-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
					{PREVIEW_END} second preview
				</span>
			)}

			{previewOver && (
				<div className="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/75 p-4 text-center text-white backdrop-blur-sm">
					<Lock className="size-7 text-primary" />
					<p className="max-w-xs text-sm">
						Buy this video to watch it all and download it.
					</p>
					<div className="flex gap-2">
						<Button onClick={onBuy}>Buy KES {VIDEO_PRICE}</Button>
						<Button
							variant="transparent"
							className="text-white"
							onClick={onReplay}>
							<RotateCcw className="size-4" />
							Replay
						</Button>
					</div>
				</div>
			)}
		</div>
	)
}
