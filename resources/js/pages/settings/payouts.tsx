import { useQueryClient } from "@tanstack/react-query"
import { Banknote, Clock, Info, UserPlus } from "lucide-react"
import { useState } from "react"
import Heading from "@/components/heading"
import { WithdrawDialog } from "@/components/payouts/withdraw-dialog"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Skeleton } from "@/components/ui/skeleton"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { commerceActions, commerceKeys, useSongPayouts } from "@/queries/commerce"
import { apiErrorMessage } from "@/queries/feed"
import { SMALL_PAYOUT_FEE, SMALL_PAYOUT_LIMIT } from "@/types/commerce"

/** The provider charge kept back from a withdrawal (SongPayout::feeFor). */
function feeFor(amount: number): number {
	return amount < SMALL_PAYOUT_LIMIT ? SMALL_PAYOUT_FEE : 0
}

/**
 * Song payouts (OLD /settings): an artist's earnings from song sales, their
 * payout history and a withdrawal to the M-Pesa number on the account.
 */
export default function Payouts() {
	const { auth } = useApp()
	const queryClient = useQueryClient()
	const isMusician = auth?.accountType === "musician"
	const payouts = useSongPayouts()

	const [confirmOpen, setConfirmOpen] = useState(false)
	const [isWithdrawing, setIsWithdrawing] = useState(false)

	const balance = payouts.data?.balance ?? 0
	const fee = feeFor(balance)
	const minimum = Number(auth?.withdrawal ?? 0)
	const hasPending = payouts.data?.songPayouts.some((p) => p.status === "pending") ?? false

	const blocker = !auth?.phone
		? "Add your M-Pesa phone number to your profile to withdraw."
		: hasPending
			? "Your last payout is still on its way. You can withdraw again once it arrives."
			: balance < minimum
				? `You can withdraw once your balance reaches KES ${minimum}.`
				: balance <= fee
					? `Your balance needs to be more than the KES ${fee} provider charge.`
					: null

	const onWithdraw = () => {
		setIsWithdrawing(true)

		commerceActions
			.withdraw()
			.then((res) => {
				toast.success(res.data.message ?? "Payout started")
				setConfirmOpen(false)
			})
			.catch((error) => toast.error(apiErrorMessage(error)))
			.finally(() => {
				setIsWithdrawing(false)
				void queryClient.invalidateQueries({ queryKey: commerceKeys.songPayouts })
			})
	}

	return (
		<>
			<Head title="Payouts | Black Music" />

			<h1 className="sr-only">Payouts</h1>

			<div className="space-y-6">
				<Heading
					variant="small"
					title="Song payouts"
					description="Earnings from your songs, paid out to M-Pesa."
				/>

				{!isMusician ? (
					<GlassCard className="p-5 text-sm text-muted-foreground">
						Payouts are for musicians. Switch your account to a musician account
						from your profile to sell songs and get paid.
					</GlassCard>
				) : (
					<>
						<GlassCard className="space-y-4 p-5">
							<div className="flex items-start justify-between gap-4">
								<div>
									<p className="text-sm text-muted-foreground">Available to withdraw</p>
									{payouts.isPending ? (
										<Skeleton className="mt-1 h-9 w-32" />
									) : (
										<p className="text-3xl font-bold tabular-nums text-primary">
											KES {balance}
										</p>
									)}
								</div>
								<div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary">
									<Banknote className="size-5" />
								</div>
							</div>

							{blocker && !payouts.isPending ? (
								<p className="text-sm text-muted-foreground">{blocker}</p>
							) : null}

							{!auth?.phone && (
								<Link
									href="/settings/profile"
									variant="outline"
									size="sm">
									Add phone number
								</Link>
							)}

							<Button
								className="w-full sm:w-auto"
								disabled={payouts.isPending || blocker !== null}
								onClick={() => setConfirmOpen(true)}>
								Withdraw KES {Math.max(balance - fee, 0)}
							</Button>
						</GlassCard>

						<GlassCard className="space-y-3 p-5 text-sm">
							<h2 className="flex items-center gap-2 font-semibold">
								<Info className="size-4 text-primary" />
								How payouts work
							</h2>
							<ul className="list-disc space-y-1.5 pl-5 text-muted-foreground">
								<li>
									You earn <span className="font-semibold text-foreground">50%</span>{" "}
									of every sale of your songs (KES 10 per video and KES 5 per
									audio).
								</li>
								<li>
									Withdrawals send your whole balance to the M-Pesa number on
									your account{auth?.phone ? ` (${auth.phone})` : ""}.
								</li>
								<li>
									On withdrawals under{" "}
									<span className="font-semibold text-foreground">
										KES {SMALL_PAYOUT_LIMIT}
									</span>
									, our provider keeps{" "}
									<span className="font-semibold text-foreground">
										KES {SMALL_PAYOUT_FEE}
									</span>{" "}
									as its charge. Bigger withdrawals are sent in full.
								</li>
								{minimum > 0 && (
									<li>
										Your minimum withdrawal is{" "}
										<span className="font-semibold text-foreground">KES {minimum}</span>.
									</li>
								)}
							</ul>
						</GlassCard>

						<GlassCard className="p-5">
							<h2 className="mb-3 font-semibold">History</h2>
							{payouts.isPending && (
								<div className="space-y-3">
									<Skeleton className="h-12 w-full" />
									<Skeleton className="h-12 w-full" />
								</div>
							)}
							{payouts.data?.songPayouts.length === 0 && (
								<p className="text-sm text-muted-foreground">No payouts yet.</p>
							)}
							<ul className="divide-y divide-border/60">
								{payouts.data?.songPayouts.map((payout) => (
									<li
										key={payout.id}
										className="flex items-center justify-between gap-3 py-3">
										<div className="min-w-0">
											<p className="font-semibold tabular-nums">KES {payout.sent}</p>
											<p className="text-xs text-muted-foreground">
												Requested {payout.created_at}
												{payout.fee > 0 && ` · KES ${payout.fee} charge`}
												{payout.paidAt && ` · Paid ${payout.paidAt}`}
											</p>
										</div>
										{payout.status === "paid" ? (
											<Badge className="border-transparent bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
												Paid
											</Badge>
										) : (
											<Badge
												variant="outline"
												className="gap-1">
												<Clock />
												Pending
											</Badge>
										)}
									</li>
								))}
							</ul>
						</GlassCard>
					</>
				)}

				<GlassCard className="flex items-center gap-4 p-5">
					<div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary">
						<UserPlus className="size-5" />
					</div>
					<div className="min-w-0 flex-1 text-sm">
						<p className="font-semibold">Invite friends</p>
						<p className="text-muted-foreground">
							Share your link and get rewarded when friends join.
						</p>
					</div>
					<Link
						href="/settings/referrals"
						variant="outline"
						size="sm">
						Invite
					</Link>
				</GlassCard>
			</div>

			{auth?.phone && (
				<WithdrawDialog
					open={confirmOpen}
					onOpenChange={setConfirmOpen}
					balance={balance}
					fee={fee}
					phone={auth.phone}
					isPending={isWithdrawing}
					onConfirm={onWithdraw}
				/>
			)}
		</>
	)
}

Payouts.layout = {
	breadcrumbs: [
		{
			title: "Payouts",
			href: "/settings/payouts",
		},
	],
}
