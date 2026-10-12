import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { cn } from "@/lib/utils"

/** "m:ss" for a number of seconds. */
export function formatTime(seconds: number): string {
	if (!Number.isFinite(seconds) || seconds < 0) {
		return "0:00"
	}

	const whole = Math.floor(seconds)
	const minutes = Math.floor(whole / 60)
	const rest = whole % 60

	return `${minutes}:${rest.toString().padStart(2, "0")}`
}

type Props = {
	/** Show the elapsed and total time under the bar. */
	showTimes?: boolean
	className?: string
}

/** A seekable progress bar for the song in the player. */
export function AudioProgress({ showTimes = false, className }: Props) {
	const { currentTime, duration, seek, current } = useAudioPlayer()
	const percent = duration > 0 ? (currentTime / duration) * 100 : 0

	return (
		<div className={cn("w-full", className)}>
			<input
				type="range"
				min={0}
				max={duration || 0}
				step={0.1}
				value={Math.min(currentTime, duration || 0)}
				disabled={!current || duration === 0}
				onChange={(event) => seek(Number(event.target.value))}
				aria-label="Seek"
				className="block h-1.5 w-full cursor-pointer appearance-none rounded-full bg-muted outline-none disabled:cursor-default [&::-moz-range-thumb]:size-3 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-0 [&::-moz-range-thumb]:bg-primary [&::-webkit-slider-thumb]:size-3 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-primary"
				style={{
					background: `linear-gradient(to right, var(--primary) ${percent}%, var(--muted) ${percent}%)`,
				}}
			/>
			{showTimes && (
				<div className="mt-1.5 flex justify-between text-xs tabular-nums text-muted-foreground">
					<span>{formatTime(currentTime)}</span>
					<span>{formatTime(duration)}</span>
				</div>
			)}
		</div>
	)
}
