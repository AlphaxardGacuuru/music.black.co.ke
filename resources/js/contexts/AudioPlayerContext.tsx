import {
	createContext,
	useCallback,
	useContext,
	useEffect,
	useLayoutEffect,
	useMemo,
	useRef,
	useState,
	type ReactNode,
} from "react"
import { useApp } from "@/contexts/AppContext"
import { mediaUrl } from "@/lib/media"
import toast from "@/lib/toast"
import type { Audio } from "@/types/music"

/**
 * The app-wide audio player (music.black's persistent bottom player). Any
 * page can start a song with play(audio, queue); it keeps playing while the
 * user navigates.
 */
export type AudioPlayerValue = {
	/** The song loaded in the player, if any. */
	current: Audio | null
	isPlaying: boolean
	/** Play a song; `queue` is what next/previous/shuffle move through. */
	play: (audio: Audio, queue?: Audio[]) => void
	/** Play or pause the current song. */
	toggle: () => void
	next: () => void
	previous: () => void
	/** Seconds into the current song and its length. */
	currentTime: number
	duration: number
	seek: (seconds: number) => void
	volume: number
	setVolume: (volume: number) => void
	shuffle: boolean
	toggleShuffle: () => void
	loop: boolean
	toggleLoop: () => void
	/** The songs next/previous move through (includes the current one). */
	queue: Audio[]
	/** True while the current song is loading or buffering. */
	isLoading: boolean
	/**
	 * Swap in fresher data for a song already in the player or queue (e.g.
	 * after it was bought) without restarting it.
	 */
	update: (audio: Audio) => void
	/**
	 * Whether the listener may play the whole song. Others get a short
	 * preview, as on music.black.
	 */
	canPlayFull: (audio: Audio | null) => boolean
	/** Stop and dismiss the player, forgetting the song and queue. */
	close: () => void
}

/** Seconds a listener who hasn't bought the song can hear. */
export const PREVIEW_SECONDS = 10

const STORAGE_KEY = "audioPlayer"

type Stored = {
	current: Audio | null
	queue: Audio[]
	volume: number
	time: number
}

function readStored(): Stored | null {
	try {
		const raw = window.localStorage.getItem(STORAGE_KEY)

		if (!raw) {
			return null
		}

		const parsed = JSON.parse(raw) as Partial<Stored>

		return {
			current: parsed.current ?? null,
			queue: Array.isArray(parsed.queue) ? parsed.queue : [],
			volume:
				typeof parsed.volume === "number"
					? Math.min(1, Math.max(0, parsed.volume))
					: 1,
			time: typeof parsed.time === "number" ? parsed.time : 0,
		}
	} catch {
		return null
	}
}

function writeStored(value: Stored) {
	try {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
	} catch {
		// Storage full or blocked; the player still works for this visit.
	}
}

const AudioPlayerContext = createContext<AudioPlayerValue | null>(null)

export function AudioPlayerProvider({ children }: { children: ReactNode }) {
	const { auth } = useApp()
	const [stored] = useState(readStored)

	const audioRef = useRef<HTMLAudioElement>(null)
	const [current, setCurrent] = useState<Audio | null>(stored?.current ?? null)
	const [queue, setQueue] = useState<Audio[]>(stored?.queue ?? [])
	const [isPlaying, setIsPlaying] = useState(false)
	const [isLoading, setIsLoading] = useState(false)
	const [currentTime, setCurrentTime] = useState(stored?.time ?? 0)
	const [duration, setDuration] = useState(0)
	const [volume, setVolumeState] = useState(stored?.volume ?? 1)
	const [shuffle, setShuffle] = useState(false)
	const [loop, setLoop] = useState(false)

	const username = auth?.username

	const canPlayFull = useCallback(
		(audio: Audio | null) =>
			Boolean(
				audio &&
					(audio.hasBoughtAudio ||
						(username !== undefined &&
							(audio.username === username || username === "@blackmusic")))
			),
		[username]
	)

	// Latest values for the <audio> event handlers and Media Session.
	const latest = useRef({ current, queue, shuffle, loop, canPlayFull })
	useLayoutEffect(() => {
		latest.current = { current, queue, shuffle, loop, canPlayFull }
	})
	const lastSavedTime = useRef(0)
	// Only report load errors for songs the listener asked to play, not the
	// song restored (paused) from the last visit.
	const userStarted = useRef(false)

	const persist = useCallback(
		(time?: number) => {
			const el = audioRef.current
			writeStored({
				current: latest.current.current,
				queue: latest.current.queue,
				volume: el?.volume ?? 1,
				time: time ?? el?.currentTime ?? 0,
			})
		},
		[]
	)

	const load = useCallback((audio: Audio, autoplay: boolean, startAt = 0) => {
		const el = audioRef.current

		if (!el) {
			return
		}

		el.src = mediaUrl(audio.audio)
		el.currentTime = startAt
		setCurrentTime(startAt)
		setDuration(0)

		userStarted.current = autoplay

		if (autoplay) {
			setIsLoading(true)
			el.play().catch(() => {
				// Autoplay blocked or the file failed; leave it paused.
				setIsLoading(false)
				setIsPlaying(false)
			})
		}
	}, [])

	const play = useCallback(
		(audio: Audio, nextQueue?: Audio[]) => {
			const el = audioRef.current
			const state = latest.current

			if (state.current?.id === audio.id && el?.src) {
				// Same song: resume rather than restart.
				if (nextQueue) {
					setQueue(nextQueue)
				}
				setCurrent(audio)
				el.play().catch(() => setIsPlaying(false))

				return
			}

			const resolvedQueue =
				nextQueue ??
				(state.queue.some((item) => item.id === audio.id)
					? state.queue
					: [audio])

			setQueue(
				resolvedQueue.some((item) => item.id === audio.id)
					? resolvedQueue
					: [audio, ...resolvedQueue]
			)
			setCurrent(audio)
			latest.current = {
				...state,
				current: audio,
			}
			load(audio, true)
		},
		[load]
	)

	const toggle = useCallback(() => {
		const el = audioRef.current

		if (!el || !latest.current.current) {
			return
		}

		if (!el.src || el.error) {
			load(latest.current.current, true)

			return
		}

		if (el.paused) {
			if (
				!latest.current.canPlayFull(latest.current.current) &&
				el.currentTime >= PREVIEW_SECONDS
			) {
				el.currentTime = 0
			}
			userStarted.current = true
			el.play().catch(() => setIsPlaying(false))
		} else {
			el.pause()
		}
	}, [load])

	const step = useCallback(
		(direction: 1 | -1, fromEnded = false) => {
			const { current: playing, queue: list, shuffle: isShuffle, loop: isLoop } =
				latest.current

			if (!playing || list.length === 0) {
				return
			}

			const index = list.findIndex((item) => item.id === playing.id)
			let nextIndex = index + direction

			if (isShuffle && list.length > 1) {
				do {
					nextIndex = Math.floor(Math.random() * list.length)
				} while (nextIndex === index)
			} else if (nextIndex < 0 || nextIndex >= list.length) {
				if (isLoop) {
					nextIndex = (nextIndex + list.length) % list.length
				} else if (fromEnded) {
					// End of the queue: stop on the last song.
					return
				} else {
					nextIndex = Math.min(Math.max(nextIndex, 0), list.length - 1)
				}
			}

			const target = list[nextIndex]

			if (target.id === playing.id) {
				const el = audioRef.current

				if (el) {
					el.currentTime = 0
					el.play().catch(() => setIsPlaying(false))
				}

				return
			}

			setCurrent(target)
			latest.current = { ...latest.current, current: target }
			load(target, true)
		},
		[load]
	)

	const next = useCallback(() => step(1), [step])

	const previous = useCallback(() => {
		const el = audioRef.current

		// Like most players, "previous" first restarts a song that's underway.
		if (el && el.currentTime > 3) {
			el.currentTime = 0

			return
		}

		step(-1)
	}, [step])

	const seek = useCallback((seconds: number) => {
		const el = audioRef.current

		if (!el || !Number.isFinite(seconds)) {
			return
		}

		const limit =
			latest.current.canPlayFull(latest.current.current) ? Infinity : PREVIEW_SECONDS
		const target = Math.max(0, Math.min(seconds, limit, el.duration || seconds))
		el.currentTime = target
		setCurrentTime(target)
	}, [])

	const setVolume = useCallback(
		(value: number) => {
			const clamped = Math.min(1, Math.max(0, value))

			if (audioRef.current) {
				audioRef.current.volume = clamped
			}
			setVolumeState(clamped)
			persist()
		},
		[persist]
	)

	const toggleShuffle = useCallback(() => {
		setShuffle((value) => !value)
		setLoop(false)
	}, [])

	const toggleLoop = useCallback(() => {
		setLoop((value) => !value)
		setShuffle(false)
	}, [])

	const update = useCallback((audio: Audio) => {
		setCurrent((value) => (value?.id === audio.id ? audio : value))
		setQueue((list) =>
			list.some((item) => item.id === audio.id)
				? list.map((item) => (item.id === audio.id ? audio : item))
				: list
		)
	}, [])

	const close = useCallback(() => {
		const el = audioRef.current

		if (el) {
			el.pause()
			el.removeAttribute("src")
			el.load()
		}

		userStarted.current = false
		latest.current = { ...latest.current, current: null, queue: [] }
		setCurrent(null)
		setQueue([])
		setIsPlaying(false)
		setIsLoading(false)
		setCurrentTime(0)
		setDuration(0)

		if ("mediaSession" in navigator) {
			navigator.mediaSession.metadata = null
		}
	}, [])

	// Restore the last song (paused) and the volume on first load.
	useEffect(() => {
		const el = audioRef.current

		if (!el) {
			return
		}

		el.volume = stored?.volume ?? 1

		if (stored?.current) {
			el.src = mediaUrl(stored.current.audio)
			el.preload = "metadata"

			const startAt = stored.time

			const onLoaded = () => {
				if (startAt > 0 && startAt < el.duration) {
					el.currentTime = startAt
				}
			}

			el.addEventListener("loadedmetadata", onLoaded, { once: true })

			return () => el.removeEventListener("loadedmetadata", onLoaded)
		}
	}, [stored])

	// Remember the song and queue whenever they change.
	useEffect(() => {
		persist()
	}, [current, queue, persist])

	// Wire the <audio> element's events to state.
	useEffect(() => {
		const el = audioRef.current

		if (!el) {
			return
		}

		const onPlay = () => setIsPlaying(true)
		const onPause = () => {
			setIsPlaying(false)
			persist()
		}
		const onPlaying = () => setIsLoading(false)
		const onWaiting = () => setIsLoading(true)
		const onDuration = () =>
			setDuration(Number.isFinite(el.duration) ? el.duration : 0)
		const onTime = () => {
			const time = el.currentTime
			setCurrentTime(time)

			if (
				!latest.current.canPlayFull(latest.current.current) &&
				time >= PREVIEW_SECONDS &&
				!el.paused
			) {
				el.pause()
				toast.info("Buy the song to keep listening.")
			}

			if (Math.abs(time - lastSavedTime.current) >= 3) {
				lastSavedTime.current = time
				persist(time)
			}
		}
		const onEnded = () => {
			setIsPlaying(false)

			if (latest.current.queue.length > 1 || latest.current.loop) {
				step(1, true)
			}
		}
		const onError = () => {
			setIsLoading(false)
			setIsPlaying(false)

			if (userStarted.current && latest.current.current) {
				toast.error(`Couldn't load ${latest.current.current.name}.`)
			}
		}

		el.addEventListener("play", onPlay)
		el.addEventListener("pause", onPause)
		el.addEventListener("playing", onPlaying)
		el.addEventListener("waiting", onWaiting)
		el.addEventListener("durationchange", onDuration)
		el.addEventListener("loadedmetadata", onDuration)
		el.addEventListener("timeupdate", onTime)
		el.addEventListener("ended", onEnded)
		el.addEventListener("error", onError)

		return () => {
			el.removeEventListener("play", onPlay)
			el.removeEventListener("pause", onPause)
			el.removeEventListener("playing", onPlaying)
			el.removeEventListener("waiting", onWaiting)
			el.removeEventListener("durationchange", onDuration)
			el.removeEventListener("loadedmetadata", onDuration)
			el.removeEventListener("timeupdate", onTime)
			el.removeEventListener("ended", onEnded)
			el.removeEventListener("error", onError)
		}
	}, [persist, step])

	// Lock-screen and headset controls.
	useEffect(() => {
		if (!("mediaSession" in navigator) || !current) {
			return
		}

		const session = navigator.mediaSession

		try {
			session.metadata = new MediaMetadata({
				title: current.name,
				artist: current.ft
					? `${current.username} ft. ${current.ft}`
					: current.username,
				album: current.album ?? undefined,
				artwork: current.thumbnail
					? [{ src: new URL(mediaUrl(current.thumbnail), window.location.origin).href, sizes: "512x512" }]
					: [],
			})

			const handlers: [MediaSessionAction, MediaSessionActionHandler][] = [
				["play", () => void audioRef.current?.play().catch(() => undefined)],
				["pause", () => audioRef.current?.pause()],
				["previoustrack", () => previous()],
				["nexttrack", () => next()],
				[
					"seekbackward",
					(details) => seek((audioRef.current?.currentTime ?? 0) - (details.seekOffset ?? 10)),
				],
				[
					"seekforward",
					(details) => seek((audioRef.current?.currentTime ?? 0) + (details.seekOffset ?? 10)),
				],
				["seekto", (details) => details.seekTime !== undefined && seek(details.seekTime)],
			]

			for (const [action, handler] of handlers) {
				try {
					session.setActionHandler(action, handler)
				} catch {
					// Action not supported by this browser.
				}
			}
		} catch {
			// Media Session unavailable.
		}
	}, [current, next, previous, seek])

	useEffect(() => {
		if ("mediaSession" in navigator) {
			navigator.mediaSession.playbackState = isPlaying ? "playing" : "paused"
		}
	}, [isPlaying])

	const value = useMemo<AudioPlayerValue>(
		() => ({
			current,
			isPlaying,
			play,
			toggle,
			next,
			previous,
			currentTime,
			duration,
			seek,
			volume,
			setVolume,
			shuffle,
			toggleShuffle,
			loop,
			toggleLoop,
			queue,
			isLoading,
			update,
			canPlayFull,
			close,
		}),
		[
			current,
			isPlaying,
			play,
			toggle,
			next,
			previous,
			currentTime,
			duration,
			seek,
			volume,
			setVolume,
			shuffle,
			toggleShuffle,
			loop,
			toggleLoop,
			queue,
			isLoading,
			update,
			canPlayFull,
			close,
		]
	)

	return (
		<AudioPlayerContext.Provider value={value}>
			{children}
			<audio
				ref={audioRef}
				preload="metadata"
				hidden
			/>
		</AudioPlayerContext.Provider>
	)
}

export function useAudioPlayer(): AudioPlayerValue {
	const value = useContext(AudioPlayerContext)

	if (!value) {
		const noop = () => undefined

		return {
			current: null,
			isPlaying: false,
			play: noop,
			toggle: noop,
			next: noop,
			previous: noop,
			currentTime: 0,
			duration: 0,
			seek: noop,
			volume: 1,
			setVolume: noop,
			shuffle: false,
			toggleShuffle: noop,
			loop: false,
			toggleLoop: noop,
			queue: [],
			isLoading: false,
			update: noop,
			canPlayFull: () => false,
			close: noop,
		}
	}

	return value
}
