import {
	Loader2,
	Pause,
	Play,
	Repeat,
	Shuffle,
	SkipBack,
	SkipForward,
	Volume1,
	Volume2,
	VolumeX,
} from "lucide-react"
import { AudioProgress } from "@/components/audio/audio-progress"
import { Button } from "@/components/ui/button"
import { PREVIEW_SECONDS, useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { cn } from "@/lib/utils"
import type { Audio } from "@/types/music"

/**
 * The full player controls on a song's page: progress, shuffle, previous,
 * play/pause, next, loop and volume. Controls the app-wide player, and
 * starts `audio` if something else is loaded.
 */
export function AudioControls({
	audio,
	queue,
	className,
}: {
	audio: Audio
	queue?: Audio[]
	className?: string
}) {
	const player = useAudioPlayer()
	const isCurrent = player.current?.id === audio.id
	const isPlaying = isCurrent && player.isPlaying

	const onPlay = () => {
		if (isCurrent) {
			player.toggle()
		} else {
			player.play(audio, queue)
		}
	}

	const VolumeIcon =
		player.volume === 0 ? VolumeX : player.volume < 0.5 ? Volume1 : Volume2

	return (
		<div className={cn("space-y-3", className)}>
			{isCurrent ? (
				<AudioProgress showTimes />
			) : (
				<div>
					<div className="h-1.5 rounded-full bg-muted" />
					<div className="mt-1.5 flex justify-between text-xs tabular-nums text-muted-foreground">
						<span>0:00</span>
						<span>0:00</span>
					</div>
				</div>
			)}

			{isCurrent && !player.canPlayFull(audio) && (
				<p className="text-center text-xs text-muted-foreground">
					Preview: the first {PREVIEW_SECONDS} seconds. Buy the song to hear it all.
				</p>
			)}

			<div className="flex items-center justify-between gap-2 sm:justify-center sm:gap-5">
				<Button
					variant="ghost"
					size="icon"
					onClick={player.toggleShuffle}
					aria-pressed={player.shuffle}
					aria-label="Shuffle"
					className={cn(player.shuffle ? "text-primary" : "text-muted-foreground")}>
					<Shuffle className="size-5" />
				</Button>
				<Button
					variant="ghost"
					size="icon"
					className="size-11"
					onClick={player.previous}
					disabled={!isCurrent}
					aria-label="Previous song">
					<SkipBack className="size-6 fill-current" />
				</Button>
				<Button
					size="icon"
					className="size-16 shadow-lg shadow-primary/30"
					onClick={onPlay}
					aria-label={isPlaying ? "Pause" : "Play"}>
					{isPlaying && player.isLoading ? (
						<Loader2 className="size-7 animate-spin" />
					) : isPlaying ? (
						<Pause className="size-7 fill-current" />
					) : (
						<Play className="size-7 translate-x-0.5 fill-current" />
					)}
				</Button>
				<Button
					variant="ghost"
					size="icon"
					className="size-11"
					onClick={player.next}
					disabled={!isCurrent || player.queue.length < 2}
					aria-label="Next song">
					<SkipForward className="size-6 fill-current" />
				</Button>
				<Button
					variant="ghost"
					size="icon"
					onClick={player.toggleLoop}
					aria-pressed={player.loop}
					aria-label="Loop the queue"
					className={cn(player.loop ? "text-primary" : "text-muted-foreground")}>
					<Repeat className="size-5" />
				</Button>
			</div>

			<div className="flex items-center justify-end gap-2">
				<button
					type="button"
					onClick={() => player.setVolume(player.volume === 0 ? 1 : 0)}
					aria-label={player.volume === 0 ? "Unmute" : "Mute"}
					className="cursor-pointer text-muted-foreground hover:text-foreground">
					<VolumeIcon className="size-4" />
				</button>
				<input
					type="range"
					min={0}
					max={1}
					step={0.01}
					value={player.volume}
					onChange={(event) => player.setVolume(Number(event.target.value))}
					aria-label="Volume"
					className="h-1 w-28 cursor-pointer appearance-none rounded-full outline-none [&::-moz-range-thumb]:size-3 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:bg-primary [&::-webkit-slider-thumb]:size-3 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-primary"
					style={{
						background: `linear-gradient(to right, var(--primary) ${player.volume * 100}%, var(--muted) ${player.volume * 100}%)`,
					}}
				/>
			</div>
		</div>
	)
}
