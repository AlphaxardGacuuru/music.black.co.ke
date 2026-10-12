import { useEcho } from "@laravel/echo-react"
import { useQueryClient } from "@tanstack/react-query"
import { Receipt as ReceiptIcon, ShoppingCart, Smartphone, Trash2, Wallet } from "lucide-react"
import { useEffect, useRef, useState } from "react"
import { PaymentDialog } from "@/components/cart/payment-dialog"
import { type Receipt, ReceiptDialog } from "@/components/cart/receipt-dialog"
import { SongRow } from "@/components/cart/song-row"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { Spinner } from "@/components/ui/spinner"
import { useApp } from "@/contexts/AppContext"
import { useAudioPlayer } from "@/contexts/AudioPlayerContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import {
	boughtToAudio,
	commerceActions,
	commerceKeys,
	useCartAudios,
	useCartVideos,
} from "@/queries/commerce"
import { audioKeys } from "@/queries/audio"
import { apiErrorMessage } from "@/queries/feed"
import { videoKeys } from "@/queries/video"
import {
	AUDIO_PRICE,
	type AudioBoughtPayload,
	type CartAudio,
	type CartVideo,
	VIDEO_PRICE,
	type VideoBoughtPayload,
} from "@/types/commerce"

/** How often, and for how long, to re-check after an STK push. */
const POLL_EVERY_MS = 4000
const POLL_FOR_MS = 60_000

const EMPTY_RECEIPT: Receipt = { videos: [], audios: [] }

const PLACEHOLDERS = [1, 2, 3]

/**
 * Cart: the videos and audios the user picked, their total against the
 * wallet balance, and either a checkout straight from the balance or an
 * M-Pesa STK push for the difference. When the payment lands the
 * server buys everything the balance covers and broadcasts VideoBoughtEvent /
 * AudioBoughtEvent; as a fallback the cart and balance are polled for a
 * minute after each push.
 */
export default function Cart() {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const player = useAudioPlayer()

	const cartVideos = useCartVideos()
	const cartAudios = useCartAudios()

	const [paymentOpen, setPaymentOpen] = useState(false)
	const [pollUntil, setPollUntil] = useState<number | null>(null)
	const [isPushing, setIsPushing] = useState(false)
	const [pushedAmount, setPushedAmount] = useState(0)
	const [receipt, setReceipt] = useState<Receipt>(EMPTY_RECEIPT)
	const [receiptOpen, setReceiptOpen] = useState(false)

	// The cart as it was when the STK push went out, to spot what got bought
	// if the broadcast never arrives.
	const snapshot = useRef<{ videos: CartVideo[]; audios: CartAudio[] } | null>(null)

	const videos = cartVideos.data ?? []
	const audios = cartAudios.data ?? []
	const videoSubtotal = videos.length * VIDEO_PRICE
	const audioSubtotal = audios.length * AUDIO_PRICE
	const total = videoSubtotal + audioSubtotal
	const balance = Number(auth?.balance ?? 0)
	const shortfall = total - balance
	// When the balance already covers the cart it's bought straight from the
	// wallet; otherwise M-Pesa is asked for the difference.
	const coveredByBalance = total > 0 && shortfall <= 0
	const amountToPay = Math.max(shortfall, 0)
	const [isCheckingOut, setIsCheckingOut] = useState(false)
	const isLoading = cartVideos.isPending || cartAudios.isPending
	const isEmpty = !isLoading && videos.length + audios.length === 0

	const refreshAfterPurchase = () => {
		void queryClient.invalidateQueries({ queryKey: commerceKeys.auth })
		void queryClient.invalidateQueries({ queryKey: commerceKeys.cartVideos })
		void queryClient.invalidateQueries({ queryKey: commerceKeys.cartAudios })
		void queryClient.invalidateQueries({ queryKey: commerceKeys.boughtVideos })
		void queryClient.invalidateQueries({ queryKey: commerceKeys.boughtAudios })
		// Song lists carry inCart / hasBought flags.
		void queryClient.invalidateQueries({ queryKey: videoKeys.all })
		void queryClient.invalidateQueries({ queryKey: audioKeys.all })
	}

	const addToReceipt = (next: Receipt) => {
		setReceipt((current) => ({
			videos: [
				...current.videos,
				...next.videos.filter((v) => !current.videos.some((c) => c.id === v.id)),
			],
			audios: [
				...current.audios,
				...next.audios.filter((a) => !current.audios.some((c) => c.id === a.id)),
			],
		}))
		setPaymentOpen(false)
		setPollUntil(null)
		setReceiptOpen(true)
	}

	// Purchase broadcasts are per buyer: video-bought.<userId>.
	useEcho<VideoBoughtPayload>(
		`video-bought.${auth?.id}`,
		"VideoBoughtEvent",
		(event) => {
			if (event.user?.id !== auth?.id) {
				return
			}

			const count = event.structuredVideos.length
			toast.success(`${count} video${count === 1 ? "" : "s"} bought`)
			addToReceipt({ videos: event.structuredVideos, audios: [] })
			refreshAfterPurchase()
		},
		[auth?.id]
	)

	useEcho<AudioBoughtPayload>(
		`audio-bought.${auth?.id}`,
		"AudioBoughtEvent",
		(event) => {
			if (event.user?.id !== auth?.id) {
				return
			}

			const count = event.structuredAudios.length
			toast.success(`${count} audio${count === 1 ? "" : "s"} bought`)
			addToReceipt({
				videos: [],
				audios: event.structuredAudios.map(boughtToAudio),
			})
			refreshAfterPurchase()
		},
		[auth?.id]
	)

	// Fallback for a missed broadcast: re-check the balance and cart for a
	// minute after an STK push.
	useEffect(() => {
		if (pollUntil === null) {
			return
		}

		const timer = window.setInterval(() => {
			if (Date.now() > pollUntil) {
				setPollUntil(null)

				return
			}

			void queryClient.invalidateQueries({ queryKey: commerceKeys.auth })
			void queryClient.invalidateQueries({ queryKey: commerceKeys.cartVideos })
			void queryClient.invalidateQueries({ queryKey: commerceKeys.cartAudios })
		}, POLL_EVERY_MS)

		return () => window.clearInterval(timer)
	}, [pollUntil, queryClient])

	// While polling, songs that left the cart were bought.
	useEffect(() => {
		if (pollUntil === null || !snapshot.current || !cartVideos.data || !cartAudios.data) {
			return
		}

		const boughtVideos = snapshot.current.videos.filter(
			(video) => !cartVideos.data.some((item) => item.id === video.id)
		)
		const boughtAudios = snapshot.current.audios.filter(
			(audio) => !cartAudios.data.some((item) => item.id === audio.id)
		)

		if (boughtVideos.length + boughtAudios.length > 0) {
			snapshot.current = null
			toast.success("Payment received")
			addToReceipt({ videos: boughtVideos, audios: boughtAudios })
			refreshAfterPurchase()
		}
	}, [cartVideos.data, cartAudios.data, pollUntil, queryClient])

	const onPay = () => {
		setIsPushing(true)
		snapshot.current = { videos, audios }

		commerceActions
			.stkPush(amountToPay)
			.then((res) => {
				toast.success(res.data.message ?? "Request sent to your phone")
				setPushedAmount(amountToPay)
				setPaymentOpen(true)
				setPollUntil(Date.now() + POLL_FOR_MS)
			})
			.catch((error) => {
				snapshot.current = null
				toast.error(apiErrorMessage(error))
			})
			.finally(() => setIsPushing(false))
	}

	const onCheckout = () => {
		setIsCheckingOut(true)

		commerceActions
			.checkout()
			.then((res) => {
				toast.success(res.data.message ?? "Songs bought")
				addToReceipt({
					videos: res.data.data.videos,
					audios: res.data.data.audios.map(boughtToAudio),
				})
				refreshAfterPurchase()
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => setIsCheckingOut(false))
	}

	const onRemoveVideo = (video: CartVideo) => {
		const previous = cartVideos.data
		queryClient.setQueryData<CartVideo[]>(commerceKeys.cartVideos, (items) =>
			items?.filter((item) => item.id !== video.id)
		)
		commerceActions
			.cartVideo(video.id)
			.then((res) => {
				if (res.data.message) toast.success(res.data.message)
				void queryClient.invalidateQueries({ queryKey: videoKeys.all })
			})
			.catch((error) => {
				queryClient.setQueryData(commerceKeys.cartVideos, previous)
				toast.error(apiErrorMessage(error))
			})
	}

	const onRemoveAudio = (audio: CartAudio) => {
		const previous = cartAudios.data
		queryClient.setQueryData<CartAudio[]>(commerceKeys.cartAudios, (items) =>
			items?.filter((item) => item.id !== audio.id)
		)
		commerceActions
			.cartAudio(audio.id)
			.then((res) => {
				if (res.data.message) toast.success(res.data.message)
				void queryClient.invalidateQueries({ queryKey: audioKeys.all })
			})
			.catch((error) => {
				queryClient.setQueryData(commerceKeys.cartAudios, previous)
				toast.error(apiErrorMessage(error))
			})
	}

	const hasReceipt = receipt.videos.length + receipt.audios.length > 0

	return (
		<>
			<Head title="Cart | Black Music" />

			<div className="mx-auto w-full max-w-5xl px-4 py-6 lg:px-6">
				<header className="mb-6 flex items-center justify-between gap-3">
					<div>
						<h1 className="text-2xl font-semibold tracking-tight">Cart</h1>
						<p className="text-sm text-muted-foreground">
							Videos are KES {VIDEO_PRICE} and audios KES {AUDIO_PRICE}. Pay
							with M-Pesa and they're yours to keep.
						</p>
					</div>
					{hasReceipt && (
						<Button
							variant="outline"
							size="sm"
							className="shrink-0 gap-1.5"
							onClick={() => setReceiptOpen(true)}>
							<ReceiptIcon className="size-4" />
							Receipt
						</Button>
					)}
				</header>

				<div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
					<div className="min-w-0 space-y-6">
						{isLoading && (
							<GlassCard className="space-y-4 p-5">
								{PLACEHOLDERS.map((key) => (
									<div
										key={key}
										className="flex items-center gap-3">
										<Skeleton className="aspect-video w-28 rounded-xl" />
										<Skeleton className="h-4 flex-1" />
									</div>
								))}
							</GlassCard>
						)}

						{isEmpty && (
							<GlassCard className="flex flex-col items-center gap-3 p-10 text-center">
								<ShoppingCart className="size-8 text-muted-foreground" />
								<p className="text-sm text-muted-foreground">
									Your cart is empty. Add songs from the charts to buy them.
								</p>
								<Link
									href="/video/charts"
									variant="outline"
									size="sm">
									Browse charts
								</Link>
							</GlassCard>
						)}

						{videos.length > 0 && (
							<GlassCard className="p-5">
								<SectionHeader
									title="Videos"
									count={videos.length}
									subtotal={videoSubtotal}
								/>
								<div className="space-y-4">
									{videos.map((video) => (
										<SongRow
											key={video.cartId}
											kind="video"
											{...video}>
											<span className="text-sm font-semibold tabular-nums text-primary">
												{VIDEO_PRICE}
											</span>
											<RemoveButton
												name={video.name}
												onClick={() => onRemoveVideo(video)}
											/>
										</SongRow>
									))}
								</div>
							</GlassCard>
						)}

						{audios.length > 0 && (
							<GlassCard className="p-5">
								<SectionHeader
									title="Audios"
									count={audios.length}
									subtotal={audioSubtotal}
								/>
								<div className="space-y-4">
									{audios.map((audio) => (
										<SongRow
											key={audio.cartId}
											kind="audio"
											{...audio}
											isPlaying={player.current?.id === audio.id && player.isPlaying}
											onPlay={() =>
												player.current?.id === audio.id
													? player.toggle()
													: player.play(audio, audios)
											}>
											<span className="text-sm font-semibold tabular-nums text-primary">
												{AUDIO_PRICE}
											</span>
											<RemoveButton
												name={audio.name}
												onClick={() => onRemoveAudio(audio)}
											/>
										</SongRow>
									))}
								</div>
							</GlassCard>
						)}
					</div>

					<aside className="lg:sticky lg:top-6 lg:self-start">
						<GlassCard className="space-y-4 p-5">
							<div className="flex items-baseline justify-between">
								<h2 className="font-semibold">Total</h2>
								<p className="text-2xl font-bold tabular-nums text-primary">
									KES {total}
								</p>
							</div>

							<div className="flex items-center justify-between rounded-2xl border border-border/60 bg-muted/40 px-4 py-3 text-sm">
								<span className="flex items-center gap-2 text-muted-foreground">
									<Wallet className="size-4" />
									Wallet balance
								</span>
								<span className="font-semibold tabular-nums">KES {balance}</span>
							</div>

							{coveredByBalance && (
								<>
									<p className="text-sm text-muted-foreground">
										Your wallet balance covers this cart. Anything left stays
										in your wallet for next time.
									</p>
									<Button
										className="w-full gap-2"
										size="lg"
										disabled={isCheckingOut}
										onClick={onCheckout}>
										{isCheckingOut ? <Spinner /> : <Wallet className="size-4" />}
										Buy with balance (KES {total})
									</Button>
								</>
							)}

							{total > 0 && !coveredByBalance && (
								<>
									<p className="text-sm text-muted-foreground">
										Tap pay and an M-Pesa prompt for{" "}
										<span className="font-semibold text-foreground">
											KES {amountToPay}
										</span>{" "}
										will pop up on your phone.
										{balance > 0 && " Your wallet balance covers the rest."}
									</p>

									{auth?.phone ? (
										<Button
											className="w-full gap-2"
											size="lg"
											disabled={isPushing || pollUntil !== null}
											onClick={onPay}>
											{isPushing || pollUntil !== null ? (
												<Spinner />
											) : (
												<Smartphone className="size-4" />
											)}
											{pollUntil !== null
												? "Waiting for payment..."
												: `Pay KES ${amountToPay} with M-Pesa`}
										</Button>
									) : (
										<div className="space-y-3 rounded-2xl border border-primary/30 bg-primary/10 p-4 text-sm">
											<p>Add your M-Pesa phone number to pay.</p>
											<Link
												href="/settings/profile"
												variant="solid"
												size="sm">
												Add phone number
											</Link>
										</div>
									)}

									{auth?.phone && (
										<p className="text-center text-xs text-muted-foreground">
											The prompt goes to {auth.phone}
										</p>
									)}
								</>
							)}
						</GlassCard>
					</aside>
				</div>
			</div>

			<PaymentDialog
				open={paymentOpen}
				onOpenChange={setPaymentOpen}
				phone={auth?.phone}
				amount={pushedAmount}
				isChecking={pollUntil !== null}
			/>

			<ReceiptDialog
				receipt={receipt}
				open={receiptOpen}
				onOpenChange={setReceiptOpen}
			/>
		</>
	)
}

function SectionHeader({
	title,
	count,
	subtotal,
}: {
	title: string
	count: number
	subtotal: number
}) {
	return (
		<div className="mb-4 flex items-baseline justify-between border-b border-border/60 pb-3">
			<h2 className="font-semibold">
				{title}{" "}
				<span className="text-sm font-normal text-muted-foreground">({count})</span>
			</h2>
			<p className="text-sm text-muted-foreground">
				Subtotal{" "}
				<span className="font-semibold tabular-nums text-primary">KES {subtotal}</span>
			</p>
		</div>
	)
}

function RemoveButton({ name, onClick }: { name: string; onClick: () => void }) {
	return (
		<Button
			variant="ghost"
			size="icon"
			className="size-8 text-muted-foreground hover:text-destructive"
			aria-label={`Remove ${name} from cart`}
			onClick={onClick}>
			<Trash2 className="size-4" />
		</Button>
	)
}
