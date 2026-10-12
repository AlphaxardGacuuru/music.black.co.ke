import {
	Download,
	Instagram,
	MessageCircle,
	MessageSquare,
	Phone,
	Sparkles,
} from "lucide-react"
import { Head } from "@/lib/spa"
import { Button } from "@/components/ui/button"
import { usePwaInstall } from "@/hooks/use-pwa-install"

// Contact links from music.black's /download page.
const CONTACTS = [
	{ label: "Call", href: "tel:0700364446", icon: Phone },
	{ label: "SMS", href: "sms:0700364446", icon: MessageSquare },
	{ label: "WhatsApp", href: "https://wa.me/254700364446", icon: MessageCircle },
	{
		label: "Instagram",
		href: "https://www.instagram.com/officialblackkenya",
		icon: Instagram,
	},
]

export default function GetApp() {
	const { canInstall, install, isInstalled } = usePwaInstall()

	return (
		<>
			<Head title="Get the app | Black Music" />
			<div className="relative isolate flex min-h-[calc(100vh-8rem)] items-center justify-center overflow-hidden rounded-2xl border bg-secondary px-6 py-16 text-secondary-foreground shadow-sm sm:px-10">
				<div className="absolute inset-0 -z-10 opacity-20 bg-[linear-gradient(135deg,transparent_25%,currentColor_25%,currentColor_26%,transparent_26%,transparent_50%,currentColor_50%,currentColor_51%,transparent_51%)] bg-size-[2rem_2rem]" />
				<div className="relative z-10 max-w-xl text-center">
					<img
						src="/android-chrome-512x512.png"
						alt="Black Music"
						className="mx-auto mb-6 size-20 rounded-3xl shadow-lg shadow-black/20"
					/>
					<div className="mb-3 flex items-center justify-center gap-2 text-sm font-medium text-primary">
						<Sparkles className="size-4" />
						It's quick and easy
					</div>
					<h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
						Get Black Music on your device
					</h1>
					<p className="mx-auto mt-5 max-w-lg text-base leading-7 text-secondary-foreground/75">
						Install the app for quick access from your home screen, a window of
						its own and music that keeps playing while you browse. It's tiny,
						no app store needed.
					</p>

					{isInstalled ? (
						<div className="mt-8 rounded-xl border border-primary/30 bg-primary/10 p-4 text-sm text-primary">
							Black Music is already installed on this device.
						</div>
					) : (
						<Button
							size="lg"
							className="mt-8 gap-2 bg-primary text-primary-foreground hover:bg-primary/90"
							disabled={!canInstall}
							onClick={() => void install()}>
							<Download className="size-5" />
							Install Black Music
						</Button>
					)}

					<div className="mt-12">
						<p className="text-sm text-secondary-foreground/75">
							Need help? Get in touch.
						</p>
						<div className="mt-3 flex justify-center gap-3">
							{CONTACTS.map((contact) => (
								<Button
									key={contact.label}
									asChild
									variant="outline"
									size="icon"
									className="size-11 rounded-full">
									<a
										href={contact.href}
										title={contact.label}
										aria-label={contact.label}
										target={contact.href.startsWith("http") ? "_blank" : undefined}
										rel="noreferrer">
										<contact.icon className="size-5" />
									</a>
								</Button>
							))}
						</div>
					</div>
				</div>
			</div>
		</>
	)
}
