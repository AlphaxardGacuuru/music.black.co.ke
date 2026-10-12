import { useNavigate } from "@tanstack/react-router"
import { MicVocal } from "lucide-react"
import { useState } from "react"
import { KaraokeAudioPicker } from "@/components/karaoke/karaoke-audio-picker"
import { KaraokeFeed } from "@/components/karaoke/karaoke-feed"
import { Button } from "@/components/ui/button"
import { useApp } from "@/contexts/AppContext"
import { Head } from "@/lib/spa"
import toast from "@/lib/toast"
import { cn } from "@/lib/utils"
import { useKaraokes, useSavedKaraokes } from "@/queries/karaoke"

export const isPublic = true

type Tab = "all" | "saved"

export default function KaraokeCharts() {
	const { auth } = useApp()
	const navigate = useNavigate()
	const [tab, setTab] = useState<Tab>("all")

	const karaokes = useKaraokes()
	const saved = useSavedKaraokes(Boolean(auth) && tab === "saved")
	const source = tab === "saved" ? saved : karaokes

	const singButton = (
		<Button
			size="sm"
			className="ml-auto h-9 rounded-full px-4 shadow-lg">
			<MicVocal className="size-4" />
			Sing
		</Button>
	)

	return (
		<>
			<Head title="Karaoke | Black Music" />
			<KaraokeFeed
				key={tab}
				karaokes={source.data ?? []}
				isPending={source.isPending}
				empty={
					source.isError
						? "Couldn't load karaokes. Try again in a moment."
						: tab === "saved"
							? "You haven't saved any karaokes. Tap the bookmark on one to keep it here."
							: "No karaokes yet. Be the first to sing one."
				}
				topBar={
					<>
						<div className="flex items-center gap-1 rounded-full bg-black/40 p-1 text-sm font-medium text-white backdrop-blur-md">
							{(
								[
									["all", "Karaoke"],
									["saved", "Saved"],
								] as const
							)
								.filter(([value]) => value === "all" || auth)
								.map(([value, label]) => (
									<button
										key={value}
										type="button"
										onClick={() => setTab(value)}
										aria-pressed={tab === value}
										className={cn(
											"rounded-full px-3.5 py-1.5 transition-colors",
											tab === value
												? "bg-primary text-primary-foreground"
												: "text-white/80 hover:text-white"
										)}>
										{label}
									</button>
								))}
						</div>
						{auth ? (
							<KaraokeAudioPicker trigger={singButton} />
						) : (
							<Button
								size="sm"
								className="ml-auto h-9 rounded-full px-4 shadow-lg"
								onClick={() => {
									toast.error("Sign in to sing a karaoke.")
									void navigate({ to: "/login" })
								}}>
								<MicVocal className="size-4" />
								Sing
							</Button>
						)}
					</>
				}
			/>
		</>
	)
}
