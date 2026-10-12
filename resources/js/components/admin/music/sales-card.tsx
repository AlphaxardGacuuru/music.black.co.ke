import type { LucideIcon } from "lucide-react"
import { Card, CardContent } from "@/components/ui/card"

type Props = {
	label: string
	sold: number
	price: number
	icon: LucideIcon
}

/**
 * Sales for one kind of song: how many were bought, the revenue, and the
 * platform's half of it (artists earn the other 50%).
 */
export function SalesCard({ label, sold, price, icon: Icon }: Props) {
	const revenue = sold * price
	const profit = Math.floor(revenue / 2)

	return (
		<Card>
			<CardContent className="space-y-4">
				<div className="flex items-center justify-between gap-3">
					<div>
						<p className="text-2xl font-semibold tracking-tight tabular-nums">
							{sold.toLocaleString()}
						</p>
						<p className="text-sm text-muted-foreground">{label}</p>
					</div>
					<div className="flex size-12 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
						<Icon className="size-6" />
					</div>
				</div>
				<dl className="grid grid-cols-3 gap-2 border-t pt-3 text-sm">
					<div>
						<dt className="text-xs text-muted-foreground">Revenue</dt>
						<dd className="font-semibold tabular-nums">KES {revenue.toLocaleString()}</dd>
					</div>
					<div>
						<dt className="text-xs text-muted-foreground">Artists</dt>
						<dd className="font-semibold tabular-nums">
							KES {(revenue - profit).toLocaleString()}
						</dd>
					</div>
					<div>
						<dt className="text-xs text-muted-foreground">Profit</dt>
						<dd className="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
							KES {profit.toLocaleString()}
						</dd>
					</div>
				</dl>
			</CardContent>
		</Card>
	)
}
