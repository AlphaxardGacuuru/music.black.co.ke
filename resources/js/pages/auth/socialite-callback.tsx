import { useEffect, useState } from "react"
import { useNavigate } from "@tanstack/react-router"
import toast from "@/lib/toast"
import { useApp } from "@/contexts/AppContext"
import { invalidateAuth } from "@/middleware/auth"
import { CheckCircle2, Circle } from "lucide-react"

type StepStatus = "pending" | "active" | "done"

const STEPS = [
	"Verifying your identity",
	"Setting up your account",
	"Opening Black Music",
]

const PROVIDER_ICONS: Record<string, React.ReactNode> = {
	google: (
		<svg
			aria-hidden="true"
			className="size-7"
			viewBox="0 0 24 24">
			<path
				fill="#4285F4"
				d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.44a5.5 5.5 0 0 1-2.39 3.61v3h3.86c2.25-2.07 3.58-5.12 3.58-8.64Z"
			/>
			<path
				fill="#34A853"
				d="M12 24c3.24 0 5.96-1.08 7.95-2.92l-3.86-3c-1.08.72-2.46 1.14-4.09 1.14-3.14 0-5.8-2.12-6.75-4.97H1.26v3.1A12 12 0 0 0 12 24Z"
			/>
			<path
				fill="#FBBC05"
				d="M5.25 14.25A7.2 7.2 0 0 1 4.87 12c0-.78.14-1.53.38-2.25v-3.1H1.26A12 12 0 0 0 0 12c0 1.93.46 3.76 1.26 5.35l3.99-3.1Z"
			/>
			<path
				fill="#EA4335"
				d="M12 4.78c1.76 0 3.34.61 4.58 1.82l3.43-3.43C17.95 1.25 15.23 0 12 0A12 12 0 0 0 1.26 6.65l3.99 3.1c.94-2.85 3.61-4.97 6.75-4.97Z"
			/>
		</svg>
	),
}

function capitalize(str: string) {
	return str.charAt(0).toUpperCase() + str.slice(1)
}

export default function SocialiteCallback() {
	const { setLocalStorage } = useApp()
	const navigate = useNavigate()

	const params = new URLSearchParams(window.location.search)
	const token = params.get("token")
	const message = params.get("message")
	const error = params.get("error")
	const provider = params.get("provider") ?? "google"

	const [stepStatuses, setStepStatuses] = useState<StepStatus[]>([
		"active",
		"pending",
		"pending",
	])

	useEffect(() => {
		const t1 = setTimeout(() => {
			setStepStatuses(["done", "active", "pending"])
		}, 500)

		const t2 = setTimeout(() => {
			setStepStatuses(["done", "done", "active"])
		}, 1000)

		const t3 = setTimeout(() => {
			setStepStatuses(["done", "done", "done"])

			if (error) {
				toast.error(error)
				navigate({ to: "/login" })
				return
			}

			if (token) {
				setLocalStorage("sanctumToken", token)
				window.localStorage.removeItem("referralCode")
				invalidateAuth()
				toast.success(message ?? "Logged in")
				navigate({ to: "/home" })
				return
			}

			toast.error("Authentication failed. Please try again.")
			navigate({ to: "/login" })
		}, 1500)

		return () => {
			clearTimeout(t1)
			clearTimeout(t2)
			clearTimeout(t3)
		}
	}, [])

	const doneCount = stepStatuses.filter((status) => status === "done").length

	return (
		<div className="flex flex-col gap-6">
			<div className="flex flex-col items-center gap-3">
				<span className="flex size-14 items-center justify-center rounded-full border bg-background shadow-md">
					{PROVIDER_ICONS[provider] ?? (
						<span className="size-6 rounded-full bg-muted" />
					)}
				</span>
				<p className="text-sm font-medium text-muted-foreground">
					Signing in with {capitalize(provider)}
				</p>
			</div>

			<div className="h-1.5 overflow-hidden rounded-full bg-primary/15">
				<div
					className="h-full rounded-full bg-primary transition-[width] duration-500 ease-out"
					style={{
						width: `${Math.max(doneCount / STEPS.length, 0.08) * 100}%`,
					}}
				/>
			</div>

			<ol className="space-y-3">
				{STEPS.map((label, i) => {
					const status = stepStatuses[i]

					return (
						<li
							key={label}
							className="flex items-center gap-3">
							<span className="flex size-5 shrink-0 items-center justify-center">
								{status === "done" && (
									<CheckCircle2 className="size-5 text-primary" />
								)}
								{status === "active" && <EqualizerIndicator />}
								{status === "pending" && (
									<Circle className="size-5 text-muted-foreground/40" />
								)}
							</span>
							<span
								className={
									status === "pending"
										? "text-sm text-muted-foreground/50"
										: status === "active"
											? "text-sm font-medium text-foreground"
											: "text-sm text-muted-foreground"
								}>
								{label}
							</span>
						</li>
					)
				})}
			</ol>
		</div>
	)
}

// A tiny three-bar equalizer in place of a spinner, reusing the backdrop's
// bounce keyframes so the in-progress step feels like the rest of the app.
function EqualizerIndicator() {
	return (
		<span
			aria-hidden="true"
			className="flex h-4 items-end gap-0.5">
			<span className="h-full w-1 origin-bottom animate-[eq-bounce-a_0.9s_ease-in-out_infinite] rounded-full bg-primary motion-reduce:animate-none" />
			<span className="h-full w-1 origin-bottom animate-[eq-bounce-b_0.7s_ease-in-out_infinite] rounded-full bg-primary motion-reduce:animate-none" />
			<span className="h-full w-1 origin-bottom animate-[eq-bounce-a_1.1s_ease-in-out_infinite_-0.4s] rounded-full bg-primary motion-reduce:animate-none" />
		</span>
	)
}

SocialiteCallback.layout = {
	title: "Welcome to Black Music",
	description: "Hang tight while we sign you in",
}
