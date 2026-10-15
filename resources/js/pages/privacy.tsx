import { ArrowLeft, ExternalLink } from "lucide-react"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"
import { Head } from "@/lib/spa"

export const isPublic = true

const POLICY_URL = "https://www.iubenda.com/privacy-policy/38639633"

/** The privacy policy, hosted by iubenda (as on music.black). */
export default function Privacy() {
	return (
		<>
			<Head title="Privacy policy | Black Music" />

			<div className="mx-auto w-full max-w-4xl lg:px-6">
				<div className="mb-4 flex items-center justify-between gap-3">
					<Link
						href="/"
						variant="ghost"
						size="sm">
						<ArrowLeft />
						Back
					</Link>
					<a
						href={POLICY_URL}
						target="_blank"
						rel="noreferrer"
						className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
						Open in a new tab
						<ExternalLink className="size-3.5" />
					</a>
				</div>

				<h1 className="mb-4 text-2xl font-semibold tracking-tight">Privacy policy</h1>

				<GlassCard className="overflow-hidden p-0">
					<iframe
						src={POLICY_URL}
						title="Privacy policy"
						className="h-[75vh] w-full bg-white"
					/>
				</GlassCard>
			</div>
		</>
	)
}
