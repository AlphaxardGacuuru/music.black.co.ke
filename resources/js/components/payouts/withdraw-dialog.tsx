import { Button } from "@/components/ui/button"
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/components/ui/dialog"
import { Spinner } from "@/components/ui/spinner"

type Props = {
	open: boolean
	onOpenChange: (open: boolean) => void
	balance: number
	fee: number
	phone: string
	isPending: boolean
	onConfirm: () => void
}

/** Confirms a withdrawal, showing exactly what will reach M-Pesa. */
export function WithdrawDialog({
	open,
	onOpenChange,
	balance,
	fee,
	phone,
	isPending,
	onConfirm,
}: Props) {
	return (
		<Dialog
			open={open}
			onOpenChange={onOpenChange}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Withdraw to M-Pesa</DialogTitle>
					<DialogDescription>
						Your whole balance is withdrawn at once and sent to {phone}.
					</DialogDescription>
				</DialogHeader>

				<dl className="space-y-2 rounded-2xl border border-border/60 bg-muted/40 p-4 text-sm">
					<div className="flex justify-between">
						<dt className="text-muted-foreground">Balance</dt>
						<dd className="tabular-nums">KES {balance}</dd>
					</div>
					<div className="flex justify-between">
						<dt className="text-muted-foreground">Provider charge</dt>
						<dd className="tabular-nums">{fee > 0 ? `- KES ${fee}` : "None"}</dd>
					</div>
					<div className="flex justify-between border-t border-border/60 pt-2 text-base font-semibold">
						<dt>You'll receive</dt>
						<dd className="tabular-nums text-primary">KES {balance - fee}</dd>
					</div>
				</dl>

				<DialogFooter>
					<Button
						variant="outline"
						disabled={isPending}
						onClick={() => onOpenChange(false)}>
						Cancel
					</Button>
					<Button
						disabled={isPending}
						onClick={onConfirm}
						className="gap-2">
						{isPending && <Spinner />}
						Send KES {balance - fee}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	)
}
