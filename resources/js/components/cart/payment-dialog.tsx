import { Smartphone } from "lucide-react"
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
	phone: string | null | undefined
	amount: number
	/** False once we've stopped checking for the payment. */
	isChecking: boolean
}

/** Shown after an STK push while we wait for the M-Pesa payment to land. */
export function PaymentDialog({
	open,
	onOpenChange,
	phone,
	amount,
	isChecking,
}: Props) {
	return (
		<Dialog
			open={open}
			onOpenChange={onOpenChange}>
			<DialogContent>
				<DialogHeader>
					<div className="mx-auto mb-1 flex size-12 items-center justify-center rounded-full bg-primary/15 text-primary sm:mx-0">
						<Smartphone className="size-6" />
					</div>
					<DialogTitle>Check your phone</DialogTitle>
					<DialogDescription>
						An M-Pesa prompt for{" "}
						<span className="font-semibold text-foreground">KES {amount}</span>{" "}
						was sent to{" "}
						<span className="font-semibold text-primary">{phone}</span>. Enter
						your PIN to pay.
					</DialogDescription>
				</DialogHeader>

				<div className="flex items-center gap-3 rounded-2xl border border-border/60 bg-muted/40 p-4 text-sm">
					{isChecking ? (
						<>
							<Spinner className="size-5 text-primary" />
							<span>Checking payment...</span>
						</>
					) : (
						<span className="text-muted-foreground">
							We haven't seen the payment yet. Your songs will be bought as
							soon as it lands, and they'll show up in your library.
						</span>
					)}
				</div>

				<DialogFooter>
					<Button
						variant="outline"
						onClick={() => onOpenChange(false)}>
						Close
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	)
}
