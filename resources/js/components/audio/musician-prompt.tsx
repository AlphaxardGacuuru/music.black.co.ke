import { Mic2 } from "lucide-react"
import { GlassCard } from "@/components/ui/glass-card"
import { Link } from "@/components/ui/link"

/** Shown to fans on studio pages: musicians only, with the way to switch. */
export function MusicianPrompt({ kind = "audio" }: { kind?: "audio" | "video" }) {
	return (
		<GlassCard className="mx-auto mt-6 w-full max-w-md p-8 text-center">
			<span className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/15 text-primary">
				<Mic2 className="size-7" />
			</span>
			<h1 className="text-xl font-bold">Become a musician</h1>
			<p className="mt-2 text-sm text-muted-foreground">
				The studio is for musicians. Switch your account to a musician account to
				upload {kind === "audio" ? "songs and albums" : "videos"} and earn from
				every download.
			</p>
			<Link
				href="/settings/profile"
				variant="solid"
				className="mt-5">
				Become a musician
			</Link>
		</GlassCard>
	)
}
