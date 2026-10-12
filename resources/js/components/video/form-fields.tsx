import type { ComponentProps, ReactNode } from "react"
import { cn } from "@/lib/utils"

/** A labelled block for fields that aren't a floating-label Input. */
export function Field({
	label,
	hint,
	children,
	className,
}: {
	label: string
	hint?: ReactNode
	children: ReactNode
	className?: string
}) {
	return (
		<div className={cn("space-y-1.5", className)}>
			<p className="px-1 text-sm font-medium">{label}</p>
			{hint && <p className="px-1 text-xs text-muted-foreground">{hint}</p>}
			{children}
		</div>
	)
}

/** A textarea styled like the app's Input. */
export function Textarea({ className, ...props }: ComponentProps<"textarea">) {
	return (
		<textarea
			className={cn(
				"min-h-28 w-full rounded-lg border border-neutral-300 bg-transparent px-4 py-3 font-nunito text-base font-light text-neutral-900 placeholder:text-neutral-500 focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-400/20 dark:border-white/20 dark:text-white dark:placeholder:text-white/50 dark:focus:border-white/40 dark:focus:ring-white/10",
				className
			)}
			{...props}
		/>
	)
}
