import { Link } from "@/components/ui/link"
import {
	Clapperboard,
	Download,
	Headphones,
	House,
	Library,
	MessagesSquare,
	MicVocal,
	Search,
	ShoppingCart,
	TrendingUp,
	Upload,
} from "lucide-react"
import { AdminNav } from "@/components/admin/AdminNav"
import AppLogo from "@/components/app-logo"
import { NavFooter } from "@/components/nav-footer"
import { NavNotifications } from "@/components/nav-notifications"
import { NavUser } from "@/components/nav-user"
import { useApp } from "@/contexts/AppContext"
import { useCurrentUrl } from "@/hooks/use-current-url"
import { usePwaInstall } from "@/hooks/use-pwa-install"
import { ADMIN_EMAIL } from "@/middleware/auth"
import { toUrl } from "@/lib/utils"
import {
	Sidebar,
	SidebarContent,
	SidebarFooter,
	SidebarGroup,
	SidebarGroupLabel,
	SidebarHeader,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
	useSidebar,
} from "@/components/ui/sidebar"
const HOME_URL = "/"
import type { NavItem } from "@/types"

// The everyday destinations, shown in the sidebar and as the mobile bottom
// bar (the same five as music.black's bottom nav).
export const mainNavItems: NavItem[] = [
	{ title: "Home", href: "/home", icon: House },
	{ title: "Karaoke", href: "/karaoke/charts", icon: MicVocal },
	{ title: "Search", href: "/search", icon: Search },
	{ title: "Library", href: "/library", icon: Library },
	{ title: "Cart", href: "/cart", icon: ShoppingCart },
]

const discoverNavItems: NavItem[] = [
	{ title: "Video charts", href: "/video/charts", icon: TrendingUp },
	{ title: "Audio charts", href: "/audio/charts", icon: Headphones },
	{ title: "Chat", href: "/chat", icon: MessagesSquare },
]

// A musician's own uploads and albums.
const studioNavItems: NavItem[] = [
	{ title: "My videos", href: "/video", icon: Clapperboard },
	{ title: "My audios", href: "/audio", icon: Upload },
]

// Sibling routes can share a path prefix (e.g. /video and /video/charts),
// so a plain prefix match would leave both nav items active. Picking the
// longest matching href resolves the ambiguity in favor of the more
// specific route, and generalizes to any future nav items with
// overlapping prefixes.
export function findActiveNavHref(
	pathname: string,
	items: NavItem[]
): string | null {
	let best: string | null = null

	for (const item of items) {
		const href = toUrl(item.href)
		const matches = pathname === href || pathname.startsWith(`${href}/`)

		if (matches && (best === null || href.length > best.length)) {
			best = href
		}
	}

	return best
}

const footerNavItems: NavItem[] = [
	{
		title: "Get App",
		href: "/get-app",
		icon: Download,
	},
]

export function AppSidebar() {
	const { state } = useSidebar()
	const { isInstalled } = usePwaInstall()
	const { auth } = useApp()
	const { currentUrl } = useCurrentUrl()
	const isAdmin = auth?.email === ADMIN_EMAIL
	const isMusician = auth?.accountType === "musician"
	const allNavItems = [...mainNavItems, ...discoverNavItems, ...studioNavItems]
	const activeNavHref = findActiveNavHref(currentUrl, allNavItems)

	const renderItems = (items: NavItem[]) =>
		items.map((item) => (
			<SidebarMenuItem key={item.title}>
				<SidebarMenuButton
					asChild
					isActive={toUrl(item.href) === activeNavHref}
					tooltip={item.title}>
					<Link href={toUrl(item.href)}>
						{item.icon && <item.icon />}
						<span>{item.title}</span>
					</Link>
				</SidebarMenuButton>
			</SidebarMenuItem>
		))

	return (
		<Sidebar
			side="right"
			collapsible="icon"
			variant="floating">
			<SidebarHeader>
				<div className="flex items-center">
					<SidebarMenu className="min-w-0 flex-1">
						<SidebarMenuItem>
							<SidebarMenuButton
								size="xl"
								asChild>
								<Link href={HOME_URL}>
									{state === "collapsed" ? (
										<AppLogo
											variant="icon"
											className="h-8"
										/>
									) : (
										<AppLogo />
									)}
								</Link>
							</SidebarMenuButton>
						</SidebarMenuItem>
					</SidebarMenu>
				</div>
			</SidebarHeader>

			<SidebarContent>
				{/* On mobile these live in the bottom bar instead. */}
				<SidebarGroup className="hidden px-2 py-0 md:block">
					<SidebarMenu>{renderItems(mainNavItems)}</SidebarMenu>
				</SidebarGroup>
				<SidebarGroup className="px-2 py-0">
					<SidebarGroupLabel>Discover</SidebarGroupLabel>
					<SidebarMenu>{renderItems(discoverNavItems)}</SidebarMenu>
				</SidebarGroup>
				{isMusician && (
					<SidebarGroup className="px-2 py-0">
						<SidebarGroupLabel>Studio</SidebarGroupLabel>
						<SidebarMenu>{renderItems(studioNavItems)}</SidebarMenu>
					</SidebarGroup>
				)}
				{isAdmin && <AdminNav />}
			</SidebarContent>

			<SidebarFooter>
				{!isInstalled && (
					<NavFooter
						items={footerNavItems}
						className="mt-auto"
					/>
				)}
				<NavNotifications />
				<NavUser />
			</SidebarFooter>
		</Sidebar>
	)
}
