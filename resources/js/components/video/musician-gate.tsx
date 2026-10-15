import { Clapperboard } from "lucide-react"
import type { ReactNode } from "react"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { useApp } from "@/contexts/AppContext"

/**
 * The studio is for musicians. Everyone else sees how to become one (switch
 * the account type in profile settings).
 */
export function MusicianGate({ children }: { children: ReactNode }) {
	const { auth } = useApp()

	if (auth?.accountType === "musician") {
		return <>{children}</>
	}

	return (
		<div className="mx-auto w-full max-w-lg px-4 py-12">
			<GlassCard className="flex flex-col items-center gap-4 p-8 text-center">
				<span className="flex size-14 items-center justify-center rounded-full bg-primary/15 text-primary">
					<Clapperboard className="size-7" />
				</span>
				<div className="space-y-1.5">
					<h1 className="text-xl font-bold">Become a musician</h1>
					<p className="text-sm text-muted-foreground">
						Musicians upload videos, sell them for KES 20 and keep half of
						every sale. Switch your account to a musician account to open the
						studio.
					</p>
				</div>
				<Link
					href="/settings/profile"
					variant="solid">
					Become a musician
				</Link>
			</GlassCard>
		</div>
	)
}
