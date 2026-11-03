import type { ReactNode } from "react"
import { useShowsPlayerBar } from "@/components/audio/audio-player-bar"
import { useSidebar } from "@/components/ui/sidebar"
import { cn } from "@/lib/utils"

/**
 * Floating buttons pinned to the bottom right of the content. They stay
 * clear of the right-hand sidebar (expanded or collapsed to icons), sit
 * above the mobile bottom nav, and rise above the mini player while it
 * shows.
 */
export function FloatingActions({
	children,
	className,
}: {
	children: ReactNode
	className?: string
}) {
	const showsPlayer = useShowsPlayerBar()
	const { state, isMobile } = useSidebar()

	return (
		<div
			className={cn(
				"fixed right-4 z-40 flex flex-col items-end gap-3 transition-[bottom,right] duration-300",
				!isMobile &&
					(state === "collapsed"
						? "md:right-[calc(var(--sidebar-width-icon)+2rem)]"
						: "md:right-[calc(var(--sidebar-width)+1rem)]"),
				showsPlayer ? "bottom-44 md:bottom-22" : "bottom-28 md:bottom-4",
				className
			)}>
			{children}
		</div>
	)
}
