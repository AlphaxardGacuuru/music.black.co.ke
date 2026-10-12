import { ArrowLeft } from "lucide-react"
import type { ReactNode } from "react"
import { Link } from "@/components/ui/link"
import { cn } from "@/lib/utils"

type Props = {
	title: ReactNode
	/** Where the back arrow goes. */
	backHref?: string
	/** Shown on the right, e.g. an avatar or a button. */
	action?: ReactNode
	className?: string
}

/** A back arrow, a title and an optional action, used across social pages. */
export function PageHeader({ title, backHref = "/", action, className }: Props) {
	return (
		<div className={cn("flex items-center gap-2", className)}>
			<Link
				href={backHref}
				variant="ghost"
				size="icon"
				aria-label="Back"
				className="shrink-0">
				<ArrowLeft className="size-5" />
			</Link>
			<h1 className="min-w-0 flex-1 truncate text-xl font-bold">{title}</h1>
			{action}
		</div>
	)
}
