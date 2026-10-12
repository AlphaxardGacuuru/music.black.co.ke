import { useNavigate } from "@tanstack/react-router"
import { useQueryClient } from "@tanstack/react-query"
import {
	CameraOff,
	Check,
	Flashlight,
	FlashlightOff,
	Headphones,
	Pause,
	Play,
	RefreshCcw,
	RotateCcw,
	SwitchCamera,
	Timer,
	Upload,
	X,
} from "lucide-react"
import { useCallback, useEffect, useRef, useState, type FormEvent, type ReactNode } from "react"
import { FileUpload } from "@/components/music/file-upload"
import { Button } from "@/components/ui/button"
import { Link } from "@/components/ui/link"
import {
	Sheet,
	SheetContent,
	SheetDescription,
	SheetHeader,
	SheetTitle,
} from "@/components/ui/sheet"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { mediaUrl } from "@/lib/media"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { apiErrorMessage } from "@/queries/feed"
import { karaokeActions, karaokeKeys } from "@/queries/karaoke"
import type { Audio } from "@/types/music"

type Stage =
	| "starting"
	| "blocked"
	| "ready"
	| "countdown"
	| "recording"
	| "paused"
	| "review"
	| "details"

type Facing = "user" | "environment"

/** What will be posted: a fresh recording, or a video already uploaded. */
type Take =
	| { kind: "recording"; blob: Blob; url: string; fileName: string }
	| { kind: "upload"; path: string }

const MIME_TYPES = [
	"video/mp4;codecs=avc1,mp4a",
	"video/mp4",
	"video/webm;codecs=vp9,opus",
	"video/webm;codecs=vp8,opus",
	"video/webm",
]

function pickMimeType(): string | undefined {
	return MIME_TYPES.find((type) => MediaRecorder.isTypeSupported(type))
}

function blockedMessage(error: unknown): string {
	const name = error instanceof DOMException ? error.name : ""

	switch (name) {
		case "NotAllowedError":
		case "SecurityError":
			return "Camera and microphone access is blocked. Allow it for this site in your browser settings, then try again."
		case "NotFoundError":
		case "OverconstrainedError":
			return "We couldn't find a camera and microphone on this device."
		case "NotReadableError":
		case "AbortError":
			return "Your camera or microphone is busy in another app. Close it and try again."
		default:
			return "Recording isn't supported in this browser."
	}
}

function formatTime(seconds: number): string {
	const safe = Number.isFinite(seconds) ? Math.max(0, Math.floor(seconds)) : 0

	return `${Math.floor(safe / 60)}:${String(safe % 60).padStart(2, "0")}`
}

/**
 * The karaoke studio: a camera preview, the song playing as a backing track,
 * and a recorder that mixes the singer's mic with the track. After a take
 * the singer reviews it, adds a description and posts it (or uploads a video
 * they already have instead).
 */
export function KaraokeRecorder({ track }: { track: Audio }) {
	const navigate = useNavigate()
	const queryClient = useQueryClient()
	const { isPlaying, toggle } = useAudioPlayer()

	const preview = useRef<HTMLVideoElement>(null)
	const backing = useRef<HTMLAudioElement>(null)
	const stream = useRef<MediaStream | null>(null)
	const audioContext = useRef<AudioContext | null>(null)
	const mixDestination = useRef<MediaStreamAudioDestinationNode | null>(null)
	const micSource = useRef<MediaStreamAudioSourceNode | null>(null)
	const recorder = useRef<MediaRecorder | null>(null)
	const chunks = useRef<Blob[]>([])
	const countdownTimer = useRef<number | undefined>(undefined)
	// Bumped on every camera (re)start so a slow, outdated request is dropped.
	const cameraRequest = useRef(0)

	const [stage, setStage] = useState<Stage>("starting")
	const [blockedReason, setBlockedReason] = useState("")
	const [facing, setFacing] = useState<Facing>("user")
	const [torchSupported, setTorchSupported] = useState(false)
	const [torchOn, setTorchOn] = useState(false)
	const [useTimer, setUseTimer] = useState(false)
	const [countdown, setCountdown] = useState(3)
	const [elapsed, setElapsed] = useState(0)
	const [duration, setDuration] = useState(0)
	const [take, setTake] = useState<Take | null>(null)
	const [showUpload, setShowUpload] = useState(false)
	const [description, setDescription] = useState("")
	const [posting, setPosting] = useState(false)
	const [progress, setProgress] = useState(0)

	const stopCamera = useCallback(() => {
		stream.current?.getTracks().forEach((t) => t.stop())
		stream.current = null
	}, [])

	const startCamera = useCallback(
		async (mode: Facing) => {
			const request = ++cameraRequest.current
			stopCamera()
			setStage("starting")
			setTorchOn(false)

			if (
				!navigator.mediaDevices?.getUserMedia ||
				typeof MediaRecorder === "undefined"
			) {
				setBlockedReason(
					"Recording isn't supported in this browser. You can still upload a video you've already recorded."
				)
				setStage("blocked")

				return
			}

			const audio: MediaTrackConstraints = {
				echoCancellation: true,
				noiseSuppression: true,
			}

			try {
				let media: MediaStream

				try {
					media = await navigator.mediaDevices.getUserMedia({
						audio,
						video: {
							facingMode: mode,
							width: { ideal: 720 },
							height: { ideal: 1280 },
						},
					})
				} catch (error) {
					// Some devices reject the size or camera hints, retry with any camera.
					if (error instanceof DOMException && error.name === "OverconstrainedError") {
						media = await navigator.mediaDevices.getUserMedia({ audio, video: true })
					} else {
						throw error
					}
				}

				if (request !== cameraRequest.current) {
					media.getTracks().forEach((t) => t.stop())

					return
				}

				stream.current = media

				if (preview.current) {
					preview.current.srcObject = media
					void preview.current.play().catch(() => undefined)
				}

				const videoTrack = media.getVideoTracks()[0]
				const capabilities = videoTrack?.getCapabilities?.() as
					| (MediaTrackCapabilities & { torch?: boolean })
					| undefined
				setTorchSupported(Boolean(capabilities?.torch))

				setStage("ready")
			} catch (error) {
				if (request !== cameraRequest.current) {
					return
				}

				setBlockedReason(blockedMessage(error))
				setStage("blocked")
			}
		},
		[stopCamera]
	)

	useEffect(() => {
		void startCamera("user")

		const backingEl = backing.current

		return () => {
			cameraRequest.current += 1
			window.clearInterval(countdownTimer.current)

			if (recorder.current && recorder.current.state !== "inactive") {
				recorder.current.onstop = null
				recorder.current.stop()
			}

			backingEl?.pause()
			stream.current?.getTracks().forEach((t) => t.stop())
			void audioContext.current?.close().catch(() => undefined)
		}
	}, [startCamera])

	// Free the recorded file when it is replaced or the page closes.
	useEffect(() => {
		return () => {
			if (take?.kind === "recording") {
				URL.revokeObjectURL(take.url)
			}
		}
	}, [take])

	/** Route the backing track and mic into one recordable audio track. */
	const prepareAudioGraph = () => {
		if (!audioContext.current && backing.current) {
			const context = new AudioContext()
			const trackSource = context.createMediaElementSource(backing.current)
			const destination = context.createMediaStreamDestination()

			// The singer hears the track; the recording gets track plus mic.
			trackSource.connect(context.destination)
			trackSource.connect(destination)

			audioContext.current = context
			mixDestination.current = destination
		}

		const context = audioContext.current
		const media = stream.current

		if (context && media && mixDestination.current) {
			if (micSource.current?.mediaStream !== media) {
				micSource.current?.disconnect()
				micSource.current = context.createMediaStreamSource(media)
				micSource.current.connect(mixDestination.current)
			}

			void context.resume()
		}
	}

	const beginRecording = () => {
		const media = stream.current
		const backingEl = backing.current

		if (!media || !backingEl || !mixDestination.current) {
			return
		}

		const mixed = new MediaStream([
			...media.getVideoTracks(),
			...mixDestination.current.stream.getAudioTracks(),
		])

		const mimeType = pickMimeType()
		let mediaRecorder: MediaRecorder

		try {
			mediaRecorder = new MediaRecorder(mixed, mimeType ? { mimeType } : undefined)
		} catch {
			toast.error("Recording isn't supported in this browser.")
			setStage("ready")

			return
		}

		chunks.current = []
		mediaRecorder.ondataavailable = (event) => {
			if (event.data.size > 0) {
				chunks.current.push(event.data)
			}
		}
		mediaRecorder.onstop = () => {
			const type = mediaRecorder.mimeType || mimeType || "video/webm"
			const blob = new Blob(chunks.current, { type })
			const extension = type.includes("mp4") ? "mp4" : "webm"

			setTake({
				kind: "recording",
				blob,
				url: URL.createObjectURL(blob),
				fileName: `karaoke-${Date.now()}.${extension}`,
			})
			setStage("review")
			stopCamera()
		}

		recorder.current = mediaRecorder
		backingEl.currentTime = 0
		setElapsed(0)

		backingEl
			.play()
			.then(() => {
				mediaRecorder.start(1000)
				setStage("recording")
			})
			.catch(() => {
				toast.error("Couldn't play the song. Tap record to try again.")
				setStage("ready")
			})
	}

	const onRecord = () => {
		// One soundtrack at a time: hush the app-wide player.
		if (isPlaying) {
			toggle()
		}

		// Set up audio inside the tap, browsers only allow sound after one.
		prepareAudioGraph()

		if (!useTimer) {
			beginRecording()

			return
		}

		setCountdown(3)
		setStage("countdown")

		let remaining = 3
		countdownTimer.current = window.setInterval(() => {
			remaining -= 1

			if (remaining <= 0) {
				window.clearInterval(countdownTimer.current)
				beginRecording()
			} else {
				setCountdown(remaining)
			}
		}, 1000)
	}

	const onPause = () => {
		recorder.current?.pause()
		backing.current?.pause()
		setStage("paused")
	}

	const onResume = () => {
		void backing.current?.play().then(() => {
			recorder.current?.resume()
			setStage("recording")
		})
	}

	const onStop = useCallback(() => {
		backing.current?.pause()

		if (recorder.current && recorder.current.state !== "inactive") {
			recorder.current.stop()
		}
	}, [])

	const onRetake = () => {
		setTake(null)
		setElapsed(0)
		void startCamera(facing)
	}

	const onFlip = () => {
		const next: Facing = facing === "user" ? "environment" : "user"
		setFacing(next)
		void startCamera(next)
	}

	const onTorch = () => {
		const videoTrack = stream.current?.getVideoTracks()[0]

		if (!videoTrack) {
			return
		}

		videoTrack
			.applyConstraints({
				advanced: [{ torch: !torchOn } as MediaTrackConstraintSet],
			})
			.then(() => setTorchOn(!torchOn))
			.catch(() => toast.error("Couldn't switch the flash."))
	}

	const onUploaded = (path: string) => {
		setShowUpload(false)
		window.clearInterval(countdownTimer.current)
		stopCamera()
		setTake({ kind: "upload", path })
		setStage("details")
	}

	const onPost = async (event: FormEvent) => {
		event.preventDefault()

		if (!take || !description.trim()) {
			return
		}

		setPosting(true)
		setProgress(0)

		try {
			const path =
				take.kind === "upload"
					? take.path
					: await karaokeActions.uploadVideo(take.blob, take.fileName, setProgress)

			const res = await karaokeActions.create({
				karaoke: path,
				audio_id: track.id,
				description: description.trim(),
			})

			toast.success(res.data.message ?? "Karaoke posted")
			void queryClient.invalidateQueries({ queryKey: karaokeKeys.all })
			void navigate({ to: "/karaoke/charts" })
		} catch (error) {
			toast.error(apiErrorMessage(error))
			setPosting(false)
		}
	}

	const isLive = stage === "recording" || stage === "paused"
	const showCamera = stage !== "review" && stage !== "details" && stage !== "blocked"
	const progressPercent = duration > 0 ? Math.min(100, (elapsed / duration) * 100) : 0

	return (
		<div className="fixed inset-0 z-50 flex items-center justify-center bg-black text-white">
			<div className="relative size-full overflow-hidden md:h-[min(100dvh-2rem,56rem)] md:max-w-[28rem] md:rounded-3xl md:border md:border-white/10">
				<audio
					ref={backing}
					src={mediaUrl(track.audio)}
					preload="auto"
					onLoadedMetadata={(event) => setDuration(event.currentTarget.duration)}
					onTimeUpdate={(event) => setElapsed(event.currentTarget.currentTime)}
					onEnded={onStop}
				/>

				{/* Camera preview (mirrored for the front camera, like a mirror) */}
				<video
					ref={preview}
					muted
					playsInline
					autoPlay
					className={cn(
						"absolute inset-0 size-full object-cover",
						facing === "user" && "-scale-x-100",
						!showCamera && "hidden"
					)}
				/>

				{/* The take, played back */}
				{take?.kind === "recording" && stage === "review" && (
					<video
						src={take.url}
						controls
						autoPlay
						loop
						playsInline
						className="absolute inset-0 size-full bg-black object-contain"
					/>
				)}

				{(stage === "details" || stage === "blocked") && (
					<img
						src={track.thumbnail}
						alt=""
						aria-hidden
						className="absolute inset-0 size-full scale-110 object-cover opacity-40 blur-2xl"
					/>
				)}

				<div className="pointer-events-none absolute inset-x-0 top-0 h-36 bg-gradient-to-b from-black/60 to-transparent" />
				<div className="pointer-events-none absolute inset-x-0 bottom-0 h-56 bg-gradient-to-t from-black/70 to-transparent" />

				{/* Top bar: close, song, tools */}
				<div className="absolute inset-x-0 top-0 flex items-start gap-3 px-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
					<Link
						href="/karaoke/charts"
						variant="unstyled"
						size="icon"
						aria-label="Close"
						className="shrink-0 rounded-full bg-black/40 text-white backdrop-blur-md">
						<X className="size-5" />
					</Link>

					<div className="flex min-w-0 flex-1 items-center gap-2 rounded-full bg-black/40 py-1 pl-1 pr-3 backdrop-blur-md">
						<img
							src={track.thumbnail}
							alt=""
							className={cn(
								"size-7 shrink-0 rounded-full object-cover animate-spin [animation-duration:6s]",
								stage !== "recording" && "[animation-play-state:paused]"
							)}
						/>
						<div className="min-w-0 text-xs leading-tight">
							<p className="truncate font-semibold text-primary">{track.name}</p>
							<p className="truncate text-white/70">{track.artistName}</p>
						</div>
					</div>

					{showCamera && (
						<div className="flex shrink-0 flex-col items-center gap-3">
							<ToolButton
								label="Flip"
								onClick={onFlip}
								disabled={isLive || stage === "countdown"}>
								<SwitchCamera className="size-5" />
							</ToolButton>
							<ToolButton
								label={useTimer ? "3s" : "Timer"}
								onClick={() => setUseTimer(!useTimer)}
								active={useTimer}
								disabled={isLive || stage === "countdown"}>
								<Timer className="size-5" />
							</ToolButton>
							{torchSupported && (
								<ToolButton
									label="Flash"
									onClick={onTorch}
									active={torchOn}>
									{torchOn ? (
										<Flashlight className="size-5" />
									) : (
										<FlashlightOff className="size-5" />
									)}
								</ToolButton>
							)}
						</div>
					)}
				</div>

				{stage === "starting" && (
					<div className="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-white/70">
						<span className="size-8 animate-spin rounded-full border-2 border-white/30 border-t-primary" />
						Starting your camera...
					</div>
				)}

				{stage === "countdown" && (
					<div
						key={countdown}
						className="absolute inset-0 flex items-center justify-center text-8xl font-bold text-primary drop-shadow-lg animate-in zoom-in-50 fade-in duration-300">
						{countdown}
					</div>
				)}

				{stage === "blocked" && (
					<div className="absolute inset-0 flex items-center justify-center p-6">
						<div className="w-full max-w-sm space-y-4 rounded-3xl border border-white/15 bg-black/50 p-6 text-center backdrop-blur-xl">
							<span className="mx-auto flex size-14 items-center justify-center rounded-full bg-primary/15 text-primary">
								<CameraOff className="size-7" />
							</span>
							<div className="space-y-1">
								<h2 className="text-lg font-semibold">Can't use your camera</h2>
								<p className="text-sm text-white/70">{blockedReason}</p>
							</div>
							<div className="flex flex-col gap-2">
								<Button onClick={() => void startCamera(facing)}>
									<RefreshCcw className="size-4" />
									Try again
								</Button>
								<Button
									variant="outline"
									className="border-white/30 bg-transparent text-white hover:bg-white/10 hover:text-white"
									onClick={() => setShowUpload(true)}>
									<Upload className="size-4" />
									Upload a video instead
								</Button>
							</div>
						</div>
					</div>
				)}

				{stage === "details" && take && (
					<form
						onSubmit={(event) => void onPost(event)}
						className="absolute inset-x-0 bottom-0 space-y-4 rounded-t-3xl border-t border-white/10 bg-black/60 p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] backdrop-blur-xl">
						<div>
							<h2 className="text-lg font-semibold">Post your karaoke</h2>
							<p className="text-sm text-white/70">
								{take.kind === "upload"
									? "Your video is uploaded. Add a description to post it."
									: "Add a description, then post it to the karaoke feed."}
							</p>
						</div>
						<textarea
							value={description}
							onChange={(event) => setDescription(event.target.value)}
							placeholder="Say something about your take"
							required
							maxLength={255}
							rows={3}
							autoFocus
							className="w-full resize-none rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm text-white outline-none placeholder:text-white/50 focus-visible:border-primary focus-visible:ring-[3px] focus-visible:ring-primary/40"
						/>
						{posting && take.kind === "recording" && (
							<div className="h-1.5 overflow-hidden rounded-full bg-white/15">
								<div
									className="h-full rounded-full bg-primary transition-[width]"
									style={{ width: `${progress}%` }}
								/>
							</div>
						)}
						<div className="flex gap-2">
							{take.kind === "recording" && (
								<Button
									type="button"
									variant="outline"
									disabled={posting}
									className="border-white/30 bg-transparent text-white hover:bg-white/10 hover:text-white"
									onClick={() => setStage("review")}>
									Back
								</Button>
							)}
							<Button
								type="submit"
								className="flex-1"
								disabled={posting || !description.trim()}>
								{posting
									? take.kind === "recording" && progress < 100
										? `Uploading ${progress}%`
										: "Posting..."
									: "Post karaoke"}
							</Button>
						</div>
					</form>
				)}

				{/* Bottom controls */}
				{stage !== "details" && stage !== "blocked" && (
					<div className="absolute inset-x-0 bottom-0 space-y-4 px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))]">
						{(isLive || stage === "ready") && (
							<div className="space-y-1.5">
								<div className="h-1 overflow-hidden rounded-full bg-white/20">
									<div
										className="h-full rounded-full bg-primary"
										style={{ width: `${progressPercent}%` }}
									/>
								</div>
								<div className="flex justify-between text-xs tabular-nums text-white/70">
									<span>{formatTime(elapsed)}</span>
									<span>{formatTime(duration)}</span>
								</div>
							</div>
						)}

						{stage === "ready" && (
							<p className="flex items-center justify-center gap-1.5 text-xs text-white/70">
								<Headphones className="size-3.5" />
								Headphones give the cleanest mix
							</p>
						)}

						<div className="grid grid-cols-3 items-center">
							<div className="flex justify-start">
								{stage === "ready" && (
									<SideButton
										label="Upload"
										onClick={() => setShowUpload(true)}>
										<Upload className="size-5" />
									</SideButton>
								)}
								{stage === "recording" && (
									<SideButton
										label="Pause"
										onClick={onPause}>
										<Pause className="size-5" />
									</SideButton>
								)}
								{stage === "paused" && (
									<SideButton
										label="Resume"
										onClick={onResume}>
										<Play className="size-5" />
									</SideButton>
								)}
								{stage === "review" && (
									<SideButton
										label="Retake"
										onClick={onRetake}>
										<RotateCcw className="size-5" />
									</SideButton>
								)}
							</div>

							<div className="flex justify-center">
								{(stage === "ready" || stage === "starting") && (
									<button
										type="button"
										onClick={onRecord}
										disabled={stage !== "ready"}
										aria-label="Start recording"
										className="flex size-20 items-center justify-center rounded-full border-4 border-white transition-transform active:scale-95 disabled:opacity-50">
										<span className="size-16 rounded-full bg-red-500" />
									</button>
								)}
								{stage === "countdown" && (
									<button
										type="button"
										onClick={() => {
											window.clearInterval(countdownTimer.current)
											setStage("ready")
										}}
										aria-label="Cancel countdown"
										className="flex size-20 items-center justify-center rounded-full border-4 border-white/60">
										<X className="size-8" />
									</button>
								)}
								{isLive && (
									<button
										type="button"
										onClick={onStop}
										aria-label="Stop recording"
										className="flex size-20 items-center justify-center rounded-full border-4 border-red-500 transition-transform active:scale-95">
										<span
											className={cn(
												"size-8 rounded-md bg-red-500",
												stage === "recording" && "animate-pulse"
											)}
										/>
									</button>
								)}
								{stage === "review" && (
									<Button
										size="lg"
										className="h-12 rounded-full px-8"
										onClick={() => setStage("details")}>
										<Check className="size-5" />
										Next
									</Button>
								)}
							</div>

							<div className="flex justify-end">
								{isLive && (
									<span className="flex items-center gap-1.5 rounded-full bg-black/40 px-3 py-1 text-xs font-medium backdrop-blur-md">
										<span
											className={cn(
												"size-2 rounded-full bg-red-500",
												stage === "recording" && "animate-pulse"
											)}
										/>
										{stage === "recording" ? "REC" : "Paused"}
									</span>
								)}
							</div>
						</div>
					</div>
				)}
			</div>

			<Sheet
				open={showUpload}
				onOpenChange={setShowUpload}>
				<SheetContent
					side="bottom"
					className="mx-auto max-w-xl rounded-t-3xl">
					<SheetHeader className="pb-0">
						<SheetTitle>Upload a karaoke video</SheetTitle>
						<SheetDescription>
							Already recorded yourself singing {track.name}? Upload the video here.
						</SheetDescription>
					</SheetHeader>
					<div className="px-4 pb-6">
						<FileUpload
							endpoint="karaokes"
							field="filepond-karaoke"
							acceptedFileTypes={["video/*"]}
							maxFileSize="200MB"
							label='Drop your video or <span class="filepond--label-action">browse</span>'
							onUploaded={onUploaded}
						/>
					</div>
				</SheetContent>
			</Sheet>
		</div>
	)
}

function ToolButton({
	label,
	onClick,
	active,
	disabled,
	children,
}: {
	label: string
	onClick: () => void
	active?: boolean
	disabled?: boolean
	children: ReactNode
}) {
	return (
		<button
			type="button"
			onClick={onClick}
			disabled={disabled}
			aria-pressed={active}
			className="flex flex-col items-center gap-1 text-[11px] font-medium drop-shadow disabled:opacity-40">
			<span
				className={cn(
					"flex size-10 items-center justify-center rounded-full backdrop-blur-md transition-colors",
					active ? "bg-primary text-primary-foreground" : "bg-black/40"
				)}>
				{children}
			</span>
			{label}
		</button>
	)
}

function SideButton({
	label,
	onClick,
	children,
}: {
	label: string
	onClick: () => void
	children: ReactNode
}) {
	return (
		<button
			type="button"
			onClick={onClick}
			className="flex flex-col items-center gap-1 text-xs font-medium">
			<span className="flex size-12 items-center justify-center rounded-full bg-white/15 backdrop-blur-md">
				{children}
			</span>
			{label}
		</button>
	)
}
