import { AppBottomNav } from "@/components/app-bottom-nav"
import { AudioPlayerBar } from "@/components/audio/audio-player-bar"
import { AppContent } from "@/components/app-content"
import { AppShell } from "@/components/app-shell"
import { AppSidebar } from "@/components/app-sidebar"
import { AppSidebarHeader } from "@/components/app-sidebar-header"
import { isFullScreenRoute } from "@/lib/bottom-nav"
import { cn } from "@/lib/utils"
import type { AppLayoutProps } from "@/types"

export default function AppSidebarLayout({
	children,
	breadcrumbs = [],
}: AppLayoutProps) {
	const fullScreen = isFullScreenRoute()

	return (
		<AppShell variant="sidebar">
			<AppContent
				variant="sidebar"
				className={cn("bg-transparent md:pb-0", fullScreen ? "pb-0" : "pb-24")}>
				{!fullScreen && (
					<AppSidebarHeader
						breadcrumbs={breadcrumbs}
						variant="floating"
					/>
				)}
				<div
					className={cn(
						"flex flex-1 flex-col overflow-x-hidden",
						fullScreen ? "" : "gap-4 p-4"
					)}>
					{children}
				</div>
				{/* Sticky, so it takes up room in the flow and never covers content. */}
				{!fullScreen && <AudioPlayerBar />}
			</AppContent>
			<AppSidebar />
			<AppBottomNav />
		</AppShell>
	)
}
